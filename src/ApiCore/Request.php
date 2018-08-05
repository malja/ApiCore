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
 * @category Request
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore;

use \malja\ApiCore\RequestMethod;
use \JsonSerializable;

/**
 * Wrapper for all request specific information - headers, received
 * data, client information, etc.
 */
class Request implements JsonSerializable
{

    /**
     * Pointer to \malja\ApiCore\RequestMethod.
     */
    protected $method = null;

    /**
     * List of all HTTP headers.
     */
    protected $headers = [];

    /**
     * Full URL for current endpoint.
     */
    protected $url = "";

    /**
     * Token class instance or null.
     */
    protected $token = null;

    /**
     * Create new instance of Request class.
     *
     * You shouldn't create this class directly. It is automatically created by App
     * class and is passed as first parameter of every controller method.
     *
     * @param object|null $token Instance of Token class as set in configuration key
     * auth/token or null for disabled authentication.
     */
    public function __construct($token = null)
    {
        // HTTP method
        $this->method = new RequestMethod($_SERVER["REQUEST_METHOD"]);

        // Current URL
        $this->url = rtrim(
            (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" .
            $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            "/"
        );

        $this->headers = getallheaders();

        $this->token = $token;
    }

    /**
     * Get request method type.
     *
     * @return RequestMethod Class with method related data.
     */
    public function method(): \malja\ApiCore\RequestMethod
    {
        return $this->method;
    }

    /**
     * Get headers.
     *
     * @return array Array with all received headers.
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Callback for `json_decode` call.
     *
     * **Note**: For security reasons, only method and url get to JSON
     * output. Headers and tokens are hidden.
     *
     * @return array Array for transformation into JSON.
     */
    public function jsonSerialize(): array
    {
        return [
            "method" => $this->method,
            "url" => $this->url,
        ];
    }

    /**
     * Get token.
     *
     * @return null|\malja\ApiCore\Auth\Token Null for disabled authentication. In other
     * cases, function returns Token.
     */
    public function token()
    {
        return $this->token;
    }
}
