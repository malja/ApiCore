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

use \JsonSerializable;

/**
 * Represents one of HTTP methods - GET, POST, PUT, etc with received data.
 */
class RequestMethod implements JsonSerializable
{

    /**
     * Contains raw string of method name all in lowercase.
     * One of: "get", "post", "put", "delete".
     *
     * @see name()
     */
    protected $method = "get";

    /**
     * Array of all data available for this method. For example, for GET,
     * content of $_GET is stored there. For DELETE and PUT method, content
     * of php://input is parsed.
     *
     * @see data()
     */
    protected $data = [];

    /**
     * Create new request method.
     *
     * @param string $method One of "put", "get", "delete", "post". Accept both
     * lower and upper case.
     */
    public function __construct(string $method)
    {
        $method = strtolower($method);

        if ($method == "post") {
            $this->data = $_POST;
        } elseif ($method == "get") {
            $this->data = $_GET;
        } elseif ($method == "delete" || $method == "put") {
            $content =file_get_contents("php://input");
            parse_str($content, $this->data);
        } else {
            throw new \Exception("Unknown request method '" . $method . "'.");
        }

        $this->method = $method;
    }

    /**
     * Return method name.
     * @return string One of "get", "post", "put", "delete".
     */
    public function name()
    {
        return $this->method;
    }

    /**
     * Get all data for this method.
     *
     * @return array
     */
    public function data()
    {
        return $this->data;
    }

    /**
     * Is this method type GET.
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->method == "get";
    }

    /**
     * Is this method type POST.
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->method == "post";
    }

    /**
     * Is this method type PUT.
     *
     * @return bool
     */
    public function isPut()
    {
        return $this->method == "put";
    }

    /**
     * Is this method type DELETE.
     *
     * @return bool
     */
    public function isDelete()
    {
        return  $this->method == "delete";
    }

    /**
     * Interface for serialization into JSON object.
     *
     * @return array Serializable data.
     */
    public function jsonSerialize()
    {
        return [
            "name" => strtoupper($this->method),
            "data" => $this->data
        ];
    }
}
