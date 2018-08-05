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

use \malja\ApiCore\Auth\TokenAuthenticator;
use \malja\ApiCore\Request;

/**
 * Class for testing token authentication. Authentication only finds Token by client
 * given public key.
 *
 * Token Class
 * ===========
 *
 * Token class for DummyTokenAuthenticator should be the same as for
 * TokenAuthenticator. But in reality, only following keys are required:
 * - `auth_token_public`.
 *
 * @see \malja\ApiCore\Auth\TokenAuthenticator
 */
class DummyTokenAuthenticator extends TokenAuthenticator
{

    /**
     * Simple create Token class from public key received from client.
     *
     * It loads `Auth-Public` header and search database for matching public key. If
     * found, returns instance of Token Class. When no record is found, returns null
     * and sets error message.
     *
     * Nonce, Datetime and Signature is not checked.
     *
     * @param \malja\ApiCore\Request $request Request for authentication.
     * @return object|null Token class if found, else null.
     */
    public function authenticate(Request $request)
    {
        $headers = $request->headers();

        $key_public = $headers["Auth-Public"] ?? "";

        $token = call_user_func($this->tokenClassName . "::findOne", [
            "auth_token_public" => $key_public,
        ]);

        if (null === $token) {
            $this->addErrorMessage("Public key doesn't exist.");
            return null;
        }

        return $token;
    }
}
