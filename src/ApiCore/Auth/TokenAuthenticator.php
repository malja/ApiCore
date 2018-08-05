<?php

/**
 * Copyright (c) 2018 Jan Malčák
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions.
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * PHP version 7.0
 *
 * @category Authentication
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore\Auth;

use \malja\ApiCore\Auth\Authenticator;
use \malja\ApiCore\Request;
use \DateTime;

/**
 * Token Authenticator uses pair of keys to authenticate each request.
 *
 * There are two keys participating in token authentication. Each user or
 * application interacting with API have to get them. **Private** keys are used
 * for signing requests. On the other hand, **public** keys acts as usernames.
 * Application will search corresponding private keys based on public ones.
 *
 * Token class
 * ===========
 *
 * When Authenticator tries to resolve if incoming request is a valid one in
 * terms of authentication, it looks into database for public and private key
 * respectively.
 *
 * This lookup is done with so-called Token class. This class is a model
 * (extends \malja\ApiCore\Model) and in addition to all rows for managing privileges
 * (which API calls are visible to them) it sets following keys required for
 * Token Authenticator.
 *
 * - `auth_token_private` - Private key, used to signature each request from
 * client.
 * - `auth_token_public` - Public key is always sent with request and acts as
 * lookup key in database.
 * - `auth_token_nonce` - Last used nonce number.
 * - `auth_enabled` - Set token as enabled or disabled. This key is required for
 * all authentication methods.
 */

class TokenAuthenticator extends Authenticator
{

    /**
     * Last client signature used for authentication.
     */
    private $signature = "";

    /**
     * Last public key used for authentication by client.
     */
    private $public_key = "";

    /**
     * List of error messages generated with last call of authenticate.
     */
    private $errorMessages = [];

    /**
     * Check if request is signed properly.
     *
     * **Required headers**:
     *
     * - `Auth-Public` - Public key. It is searched within database to get
     * private key.
     * - `Auth-Signature` - Client signature of request. Signature is calculated
     * by `hmac` function with `sha512` algorithm. Key is client private key.
     * Data is nonce and query encoded data.
     * - `Auth-Datetime` - Datetime used for signature in ISO-8601 format.
     *
     * **Required data**:
     *
     * - `nonce` - Unique number for each request. It is recommended to
     * increment it with each request. Server checks, if received nonce is
     * greater number than the last one stored in `auth_token_nonce`.
     *
     * **Note**: Requests older than 30 second are automatically discarded and
     * considered invalid.
     *
     * @param \malja\ApiCore\Request $request Request data.
     * @return null|object Null when authentication failed, instance of Token
     * class otherwise.
     */
    public function authenticate(Request $request)
    {
        // Make sure error messages are empty in the beginning
        $this->errorMessages = [];

        // Get all headers from request
        $headers = $request->headers();

        // List of all required headers for this type of authentication
        $required_headers = [
            "Auth-Public",
            "Auth-Signature",
            "Auth-Datetime",
        ];

        // Check if they are present
        foreach ($required_headers as $h) {
            if (!array_key_exists($h, $headers)) {
                $this->addErrorMessage(
                    "HTTP header '$h' is required for Token Authentication."
                );
            }
        }

        // Get all body data
        $data = $request->method()->data();

        // List of all keys required in data array
        $required_data = [
            "nonce",
        ];

        // Check presence of required keys
        foreach ($required_data as $d) {
            if (!array_key_exists($d, $data)) {
                $this->addErrorMessage(
                    "Body key '$d' is required."
                );
            }
        }

        // Fill data for jsonSerialize
        $this->signature = $headers["Auth-Signature"] ?? "";
        $this->public_key = $headers["Auth-Public"] ?? "";

        // If there are error messages, return null.
        // With this behavior, all missing headers and data keys are listed,
        // not just the first one.
        if (!empty($this->errorMessages)) {
            return null;
        }

        // Get data from headers
        $key_public = $headers["Auth-Public"];
        $signature = $headers["Auth-Signature"];
        $datetime = $headers["Auth-Datetime"];

        // Get data from body
        $nonce = $data["nonce"];

        // Client and server time
        $client_time = new Datetime($datetime);
        $server_time = new Datetime();

        // Too old request
        if ($client_time->diff($server_time)->s > 30) {
            $this->addErrorMessage("Client request is too old.");
            return null;
        }

        // Encode data array
        $body = http_build_query($data);

        // Search for token by it's public key
        $token = call_user_func($this->tokenClassName . "::findOne", [
            "auth_token_public" => $key_public,
        ]);

        // Public token doesn't exist
        if (null === $token) {
            $this->addErrorMessage("Public key doesn't exist.");
            return null;
        }

        if (!$token->enabled) {
            $this->addErrorMessage("Token is disabled.");
            return null;
        }

        // Validate nonce against last used nonce
        if ($token->auth_token_nonce >= $nonce) {
            $this->addErrorMessage("Invalid nonce '$nonce'.");
            return null;
        }

        // Create signature for comparison
        $server_signature = \hash_hmac(
            "sha512",
            (string) $nonce . $body,
            $token->auth_token_private
        );

        // Signatures don't match
        if (!hash_equals($server_signature, $signature)) {
            $this->addErrorMessage(
                "Signature mismatch. Server: '$server_signature'"
            );
            return null;
        }

        // Update nonce value
        $token->auth_token_nonce = $nonce;
        $token->save();

        return $token;
    }

    /**
     * Callback for serialization into JSON object.
     *
     * @return array Array for serialization. It contains authenticator name,
     * error messages (if any), public key and signature.
     */
    public function jsonSerialize()
    {
        return [
            "name" => "TokenAuth",
            "key_public" => $this->public_key,
            "signature" => $this->signature,
            "errors" => $this->getErrorMessages(),
        ];
    }

    /**
     * Adds error message to the list of all messages occurred during last call
     * of method authenticate.
     *
     * @param string $message Message which should be added.
     * @return void Message is automatically added to list without any return
     * code.
     */
    protected function addErrorMessage(string $message)
    {
        array_push($this->errorMessages, $message);
    }

    /**
     * Get list of all error messages for last call of authenticate method.
     *
     * @return array List of all errors.
     * @see authenticate()
     */
    public function getErrorMessages(): array
    {
        return $this->errorMessages;
    }
}
