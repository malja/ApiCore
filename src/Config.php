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
 * @category Configuration
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore;

use malja\ApiCore\Scheme;

/**
 * Load, merge and serve content of multiple configuration files.
 */
class Config
{
    /**
     * Array with merged configuration from all configuration files.
     */
    protected $config = [];

    /**
     * Load PHP configuration file.
     *
     * Configuration file is a PHP file, which returns an array with configuration.
     *
     * **Validation**: When `$scheme` parameter is set, array returned from config
     * will be validated. If valid, load file will return true. When failed,
     * scheme validation exception is thrown.
     *
     * @param string $path Path to configuration file.
     * @param malja\ApiCore\Scheme Scheme for validation or null for no validation.
     *
     * @throws \InvalidArgumentException If `$path` doesn't exist or file is not a PHP file.
     * @throws \Exception If configuration file returns something else than array.
     * @throws \Garden\Schema\ValidationException If validation fails.
     *
     * @return bool Always true.
     */
    public function loadFile(string $path, Scheme $scheme = null)
    {

        // Check if file exists and if it is a PHP file
        if (!file_exists($path) || pathinfo($path, PATHINFO_EXTENSION) != "php") {
            throw new \InvalidArgumentException(
                "Configuration file '$path' does not exist, or it isn't a PHP file"
            );
        }

        // Get data
        $data = include $path;

        // Check if we got a PHP array
        if (!is_array($data)) {
            throw new \Exception(
                "Configuration file '$path' doesn't return PHP array"
            );
        }

        // Validate data with scheme
        if (null !== $scheme) {
            $scheme->validate($data);
        }

        // Merge config arrays
        $this->config = array_merge($this->config, $data);

        return true;
    }

    /**
     * Set value of key.
     *
     * **Example**:
     *
     *     // $config is Config class instance
     *     // Set $config["a"] = "new value"
     *     // Set $config["b"]["c"] = "val2"
     *     $config->set("a", "new value")->set("b/c", "val2");
     *
     * @param string $path Key name or key sequence. Key sequence is separated by slash and
     * acts as search in multidimensional array. For example `set("a/b/c/d")` means that
     * function is setting new value of `$config["a"]["b"]["c"]["d"]`.
     *
     * @param mixed $value New value for key.
     *
     * @param bool $strict In strict mode, exception is raised when key doesn't exist.
     *
     * @return \malja\ApiCore\Config Configuration instance.
     */
    public function set(string $path, $value, bool $strict = true)
    {
        if (empty($path)) {
            throw new \UnexpectedValueException("Path cannot be empty");
        }

        if ($strict) {
            if (!$this->has($path)) {
                throw \Exception(
                    "Setting non-existing key isn't allowed in strict mode"
                );
            }
        }

        $keys = explode("/", $path);

        $data = [];
        $data_ref = &$data;

        foreach ($keys as $key) {
            $data_ref[$key] = [];
            $data_ref = &$data_ref[$key];
        }

        $data_ref = $value;
        unset($data_ref);

        $this->config = array_merge_recursive($this->config, $data);

        return $this;
    }

    /**
     * Return value of key or default value.
     *
     * **Example**:
     *
     *     // $config is Config class instance
     *     $config->get("a"); // Get value of key "a" directly from $config
     *
     *     $config->get("a/b"); // Get value of key "b" located under key "a"
     *
     * @param string $path Key name or key sequence. Key sequence is separated by slash and
     * acts as search in multidimensional array. For example `get("a/b/c/d")` means that
     * function is returning value of `$config["a"]["b"]["c"]["d"]`.
     *
     * @param mixed $default Default value used when key doesn't exist.
     *
     * @return mixed Key value or default value.
     */
    public function get(string $path, $default = null)
    {
        if (empty($path)) {
            return $default;
        }

        // Setup for loop
        $data = $this->config;
        $keys = explode("/", $path);

        // Search in array
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                return $default;
            }

            $data = $data[$key];
        }

        return $data;
    }

    /**
     * Check if key exists in configuration.
     *
     * **Example**:
     *
     *     // $config is Config class instance
     *     $config->has("a"); // Searching for key "a" directly in $config array.
     *
     *     $config->has("a/b"); // Looking for key "b" with parent "a".
     *
     * @param string $path Key name or key sequence. Key sequence is separated by slash and
     * acts as search in multidimensional array. For example `has("a/b/c/d")` means that
     * function is looking for `$config["a"]["b"]["c"]["d"]`.
     *
     * @throws \UnexpectedValueException If `$path` is empty.
     *
     * @return bool True if key exists.
     */
    public function has(string $path)
    {

        // Path to key cannot be empty
        if (empty($path)) {
            throw new \UnexpectedValueException(
                "Path cannot be empty"
            );
        }

        // If it is just one key, try to find it
        if (strpos($path, "/") == false) {
            return array_key_exists($path, $this->config);
        }

        // Setup for loop
        $data = $this->config;
        $keys = explode("/", $path);

        // Search in array
        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                return false;
            }

            $data = $data[$key];
        }

        return true;
    }
}
