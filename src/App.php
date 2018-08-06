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
 * @category Application
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore;

use PicORM\PicORM;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use \Exception;
use \malja\ApiCore\Auth\AuthFailedException;
use \malja\ApiCore\Config;
use \malja\ApiCore\Request;
use \malja\ApiCore\Response;
use \malja\ApiCore\Router;
use \PDO;

/**
 * App is root of whole apiCore application. It groups router, database, error handler
 * and other crucial parts up.
 *
 * App is created in index with path to application root. Then, its `run` method is
 * run, which takes care of all routing and rendering.
 */
class App
{

    /**
     * Path to application root without trailing slash.
     */
    public static $path = "";

    /**
     * Pointer to PDO database wrapper.
     */
    private $db = null;

    /**
     * Pointer to Router instance.
     */
    private $router = null;

    /**
     * Pointer to Whoops error handler.
     */
    private $errorHandler = null;

    /**
     * Debug mode switch.
     */
    private static $debug = false;

    /**
     * Pointer to Config instance.
     */
    private $config = null;

    /**
     * Create apiCore application.
     *
     * @param string $path Path to application root.
     */
    public function __construct(string $path)
    {
        self::$path = rtrim($path, '/');

        $this->setAutoloader();
        $this->setErrorHandler();

        $this->setConfig();

        $this->setDatabase();
        $this->setRouter();
    }

    /**
     * Create instance of \malja\ApiCore\Router and load routes from 'app/routes.php'.
     *
     * **Note**: This method requires loaded configuration.
     * @throws \Exception When file with routes doesn't exist or it doesn't return
     * PHP array.
     * @return bool Always return true.
     */
    protected function setRouter(): bool
    {

        //Create router instance
        $this->router = new Router();

        // Setup base path
        $this->router()->setBasePath(
            $this->config()->get("url/base")
        );

        // Check if app configuration with routes exists
        if (!file_exists(self::path() . "/app/routes.php")) {
            throw new Exception(
                "File 'app/routes.php' doesn't exist, or it isn't readable"
            );
        }

        // Get routes
        $routes = include self::path() . "/app/routes.php";
        if (!is_array($routes)) {
            throw new Exception("File 'app/routes.php' have to return an array");
        }

        // and register them
        $this->router()->addRoutes($routes);
        return true;
    }

    /**
     * Create config instance and load config from 'config.php'.
     */
    protected function setConfig()
    {
        $this->config = new Config;
        $this->config->loadFile(self::path() . "/config.php");
    }

    /**
     * Crete PDO instance and connect PicORM library to it.
     *
     * **Note**: This method requires loaded configuration.
     */
    protected function setDatabase()
    {
        $db_config = [
            "dbname" => $this->config()->get("database/name"),
            "host" => $this->config()->get("database/host", "localhost"),
        ];

        $dns = "mysql:" . http_build_query($db_config, "", ";");

        $this->db = new PDO(
            $dns,
            $this->config()->get("database/user"),
            $this->config()->get("database/pass"),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]
        );

        // Configure ORM library
        PicORM::configure([
            "datasource" => $this->db,
        ]);
    }

    /**
     * Register default autoloader
     */
    protected function setAutoloader()
    {
        \malja\ApiCore\DefaultAutoloader::register();
    }

    /**
     * Set event handler. It is enabled in `run` method, when debug mode is enabled.
     */
    protected function setErrorHandler()
    {
        $this->errorHandler = new Run();
        $this->errorHandler->pushHandler(new PrettyPageHandler());
        //$this->errorHandler->pushHandler(new JsonResponseHandler());
    }

    /**
     * With enabled authentication (auth/enable) it checks incomming request and
     * runs authentication class (auth/type).
     *
     * @return null|object Instance of class set with (auth/token) or null when
     * authentication is not enabled.
     *
     * @throws \Exception When authentication fails.
     */
    protected function checkAuth()
    {

        // Authentication is checked only when enabled in configuration
        if ($this->config()->get("auth/enable", true) != true) {
            return null;
        }

        // Create request without token, after authentication, new request will be
        // created with appropriate token.
        $request = new Request;

        // Token with private and public keys
        $token = null;

        // Get class name for authentication and token
        $auth_type = $this->config()->get("auth/type");
        $token_type = $this->config()->get("auth/token");

        // If auth type is set
        if (null !== $auth_type) {
            if (null == $token_type) {
                throw new Exception("Configuration key 'auth/token' is not set");
            }

            // Create authentication class
            $auth = new $auth_type($token_type);

            // Authenticate
            $token = $auth->authenticate($request);

            // When authentication fails, token is null
            if (null == $token) {
                throw new AuthFailedException($auth);
            }
        }

        return $token;
    }

    /**
     * Find route, create controller and execute corresponding method.
     *
     * @param array $data Additional parameters for controller.
     *
     * @return mixed Anything returned from controller which should be sent to output.
     */
    protected function route(array $data)
    {

        // Get current URL and method
        $url = rtrim(
            (isset($_SERVER['HTTPS']) ? "https" : "http") . "://" .
            $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
            "/"
        );

        $method = $_SERVER['REQUEST_METHOD'];

        // Get route
        $route = $this->router()->match($url, $method);

        // No route found
        if (empty($route)) {
            return Response::NotFound([
                "url" => $url,
                "method" => $method,
            ]);
        }

        // Merge $data array and the rest of URL parameters
        $parameters = array_merge($data, array_values($route["params"]));

        // Get controller name
        $target_parts = explode("#", $route["target"]);
        $controller_name = $target_parts[0];
        $controller_method = $target_parts[1];

        // Create controller instance
        $controller = new $controller_name($this);

        // Call it's method
        return call_user_func_array(
            array(
                $controller,
                $controller_method,
            ),
            $parameters
        );
    }

    /**
     * Start apiCore application. Routing is enabled, error handler registered (if
     * debug mode is set).
     *
     * @param bool $debug Enable debug mode. In debug mode, all errors are printed out
     * to output.
     */
    public function run(bool $debug = false)
    {

        // Error handler is registered only in debug mode
        if ($debug) {
            $this->errorHandler->register();
            error_reporting(E_ALL);
        }

        // Data to be json_encode-d for output
        $output_data = null;

        // Authenticate if necessary
        try {
            $token = $this->checkAuth();
        } catch (AuthFailedException $e) {
            $req = new Request;
            $output_data = Response::unauthorized(
                $e->getAuth()
            );
        }

        if (null == $output_data) {
            $output_data = $this->route([
                new Request($token),
            ]);
        }

        echo json_encode(
            $output_data,
            JSON_PRETTY_PRINT
        );
    }

    /**
     * Get configuration.
     *
     * @return \malja\ApiCore\Config Configuration.
     */
    public function config(): \malja\ApiCore\Config
    {
        return $this->config;
    }

    /**
     * Return router.
     *
     * @return \malja\ApiCore\Router Router instance.
     */
    public function router(): \malja\ApiCore\Router
    {
        return $this->router;
    }

    /**
     * Return PDO connection.
     *
     * @return \PDO PDO instance.
     */
    public function db(): \PDO
    {
        return $this->db;
    }

    /**
     * Return path to ApiCore root.
     *
     * **Note**: Value is empty before application is created!
     *
     * @return string Path to root without trailing slash.
     */
    public static function path(): string
    {
        return self::$path;
    }
}
