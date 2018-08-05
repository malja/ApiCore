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
 * @category Response
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace core;

use core\Model;
use core\PicORM\Collection;
use \JsonSerializable;

/**
 * Response class is responsible for taking whatever data you pass in and
 * turning it into valid JSON string. This string is rendered on page.
 *
 * Most of the time, you should not create an instance directly, but rather use
 * one of it's static methods. They are designed to "cover" most used HTTP
 * response codes and taking care of coherent output.
 */
class Response implements JsonSerializable
{

    /**
     * HTTP status code.
     */
    private $code = 200;

    /**
     * Contains all data extracted from $data parameter of constructor. This
     * array is rendered to output later on.
     */
    private $data = array();

    /**
     * Create new instance of Response.
     *
     * If you don't have to, do not create instance directly. Use one of static
     * methods dedicated for HTTP return code.
     *
     * @param int $code HTTP status code. See list of them at
     * https://www.restapitutorial.com/httpstatuscodes.html
     *
     * @param array|core\PicORM\Collection|core\Model $data Data for output.
     *
     * @throws \InvalidArgumentException When setData() method fails to get
     * data.
     */
    public function __construct(int $code, $data)
    {
        $this->code = $code;

        $this->setData($data);
    }

    /**
     * This function is for creating 200 OK status response from server. Use it
     * for outputting when the request succeeded.
     *
     * @param array|\core\Model $data   Data parameter should contain all data
     * found for required resource. In most cases, just pass model instance in.
     * Rarely, array with pre-fetched data can be used.
     *
     * @throws \InvalidArgumentException When `$data` is not one of required
     * types.
     */
    public static function ok($data)
    {
        return new Response(200, $data);
    }

    /**
     * Response for 400 Bad Request. Use it when request could not be understood
     * by the server (syntax error). In this case, client should not repeat the
     * request without modification.
     *
     * **Note**: Follows list of required keys:
     *
     * - `request` - Array containing client request as PHP array.
     *
     * @param array $data Additional data.
     *
     * @throws \InvalidArgumentException When `$data` doesn't contain required
     * keys.
     */
    public static function badRequest(array $data)
    {

        // Check for "request" key
        if (!array_key_exists("request", $data)) {
            throw new \InvalidArgumentException(
                "BadRequest requires 'request' key filled with client request"
            );
        }

        return new Response(400, array_merge(
            [
                "error" => "Received data is not valid",
            ],
            $data
        ));
    }

    /**
     * Response for 404 Not Found. Whenever the server hasn't found something
     * based on request, this is the right response for it.
     *
     * **Note**: One of `id` or `url` keys is required.
     *
     * - `id` - ID of resource, which was not found. When `id` is used,
     * `resource` key is required as well.
     * - `resource` - Name of resource in which was not the ID found. For
     * example users, pages, notes, ...
     * - `url` - URL of page, which does not exist.
     *
     * @param array $data Additional data.
     *
     * @throws \InvalidArgumentException When `$data` doesn't contain required
     * keys.
     */
    public static function notFound(array $data)
    {

        // One of "id" or "url" has to be set.
        if (!array_key_exists("id", $data) && !array_key_exists("url", $data)) {
            throw new \InvalidArgumentException(
                "Method requires one of 'id' or 'url' keys to be set"
            );
        }

        // With ID, there have to be resource name
        if (array_key_exists("id", $data) &&
            !array_key_exists("resource", $data)) {
            throw new \InvalidArgumentException(
                "Method requires 'resource' key set when 'id' key is present"
            );
        }

        // Prepare response from error message and data
        return new Response(404, array_merge(
            [
                "error" => "Resource or URL endpoint doesn't exist",
            ],
            $data
        ));
    }

    /**
     * Response for 201 Created status code. Use it after successful creation of
     * new resource.
     *
     * **Note**: There are two required keys:
     *
     * - `id` - ID of created resource
     * - `resource` - Resource name. For example users, posts, notes, ...
     *
     * @param array $data Additional data.
     *
     * @throws \InvalidArgumentException When `$data` doesn't contain required
     * keys.
     */
    public static function created(array $data)
    {
        if (!array_key_exists("id", $data) ||
            !array_key_exists("resource", $data)) {
            throw new \InvalidArgumentException(
                "Method requires both 'id' and 'resource' keys set"
            );
        }

        return new Response(201, $data);
    }

    /**
     * This method doesn't have counterpart in any HTTP status codes. But it is
     * used for returning 200 OK after resource is deleted.
     *
     * **Note**: Follows list of required keys:
     *
     * - `id` - ID of deleted resource.
     * - `resource` - Resource name. For example users, posts, notes, ...
     *
     * @param array $data Additional data.
     *
     * @throws \InvalidArgumentException When `$data` doesn't contain required
     * keys.
     */
    public static function deleted(array $data)
    {
        if (!array_key_exists("id", $data) ||
            !array_key_exists("resource", $data)) {
            throw new \InvalidArgumentException(
                "Method requires both 'id' and 'resource' keys set"
            );
        }

        return new Response(200, array_merge(
            [
                "done" => "Resource was deleted",
            ],
            $data
        ));
    }

    /**
     * This method doesn't have counterpart in any HTTP status codes. It is used
     * for returning 200 OK after resource is updated.
     *
     * **Note**: Follows list of required keys:
     *
     * - `id` - ID of deleted resource.
     * - `resource` - Resource name. For example users, posts, notes, ...
     *
     * @param array $data Additional data.
     *
     * @throws \InvalidArgumentException When `$data` doesn't contain required
     * keys.
     */
    public static function updated(array $data)
    {
        if (!array_key_exists("id", $data) ||
            !array_key_exists("resource", $data)) {
            throw new \InvalidArgumentException(
                "Method requires both 'id' and 'resource' keys set"
            );
        }

        return new Response(200, array_merge(
            [
                "done" => "Resource was updated",
            ],
            $data
        ));
    }

    /**
     * Response for 500 Internal Server Error. This method is used when database
     * operations fail, or any other server side error occurs.
     */
    public static function serverError()
    {
        return new Response(500, [
            "error" => "There was an error during processing your request.",
        ]);
    }

    /**
     * Request did not pass authentication. This response is created from
     * application when selected authentication method failed to validate
     * client data.
     *
     * @param object $auth Authentication object.
     */
    public static function unauthorized($auth)
    {
        return new Response(
            401,
            [
                "error" => "Request was not authenticated",
                "auth" => $auth,
            ]
        );
    }

    public static function forbidden()
    {
        return new Response(403, [
            "error" => "Insuficcient rights",
        ]);
    }

    /**
     * Serialize response into JSON object.
     *
     * @return array All serializable data.
     */
    public function jsonSerialize()
    {
        // Always respond with JSON
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");

        http_response_code($this->code);

        return $this->data;
    }

    /**
     * Parse data and save it in right format.
     *
     * @param array|\core\PicORM\Collection|\core\Model|object Data source.
     * @throws \InvalidArgumentException When `$data` is not one of required
     * types.
     */
    protected function setData($data)
    {
        if ($data instanceof Model) {
            $this->data = $data->toArray();
        } elseif ($data instanceof Collection) {
            foreach ($data as $model) {
                array_push($this->data, $model->toArray());
            }
        } elseif (is_array($data)) {
            $this->data = $data;
        } elseif (is_object($data) && method_exists($data, "jsonSerialize")) {
            $this->data = $data->jsonSerialize();
        } else {
            throw new \InvalidArgumentException(
                "Parameter _data_ used for creating Response class have to be
                one of following types: array, \core\Model,
                \core\PicORM\Collection. Got type: " . gettype($data)
            );
        }
    }
}
