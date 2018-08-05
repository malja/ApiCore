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
 * @category Autoloader
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore;

use \malja\ApiCore\Autoloader;

/**
 * Default autoloader splits class name and namespace. Namespace is
 * used as a path to a file. The filename must match the class name.
 * For example \\this\\is\\path\\ClassOfMine points to file **ClassOfMine.php**
 * under /this/is/path.
 */
class DefaultAutoloader extends Autoloader
{

    /**
     * Tries to find missing class and autoload it.
     *
     * @param string $class Class or interface name which is missing.
     * @return bool Return `true` if class was found.
     */
    public static function autoload(string $class): bool
    {
        $class_name = $class;

        // Remove leading a trailing backslash
        $class = trim($class, "\\");

        // Remove unwanted characters
        $class = str_replace([".", "/", "\\"], ["", "", "/"], $class);

        $parts = explode("/", $class);

        // File name with path
        $path = App::path() . "/" . $class . ".php";

        // File doesn't exist
        if (!file_exists($path)) {
            return false;
        }

        // Include file
        require_once $path;

        // Class or interface doesn't exist
        if (!class_exists($class_name) && !interface_exists($class_name)) {
            return false;
        }

        return true;
    }
}
