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

/**
 * Autoloader is base class for all autoloaders, it defines two methods for
 * registering and unregistering itself and one method which autoloads missing
 * class.
 */
class Autoloader
{

    /**
     * Autoload function is called when interface or class is missing. Reference name
     * is passed as the first parameter.
     *
     * @param string $class Class or interface name.
     * @return bool Return `true` if class is found.
     */
    public static function autoload(string $class): bool
    {
        throw new Exception("Extend this function. Do not call it!");
    }

    /**
     * Registers itself as an autoloader.
     *
     * **Do not overwrite**: This method should not be overwritten in child class.
     */
    public static function register()
    {
        spl_autoload_register([get_called_class(), "autoload"]);
    }

    /**
     * Unregisters itself from list of autoloaders.
     *
     * **Do not overwrite**: This method should not be overwritten in child class.
     */
    public static function unregister()
    {
        spl_autoload_unregister([get_called_class(), "autoload"]);
    }
}
