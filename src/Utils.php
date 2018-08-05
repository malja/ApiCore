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
 * @category Utilities
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore;

/**
 * Set of helpful utilities.
 */
class Utils
{

    /**
     * Change CamelCaseName to unserscore_separated_name.
     * @param $name String with CamelCase name.
     * @return String with name changed to underscore_separated form.
     */
    public static function camelToUnderscore($name)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $name, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = strtoupper($match) == $match ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * A spiritual port of Python's urlparse.urljoin() function to PHP.
     * @author fluffy, http://beesbuzz.biz/
     * @link https://github.com/plaidfluff/php-urljoin
     */
    public static function urljoin($base, $rel)
    {
        $pbase = parse_url($base);
        $prel = parse_url($rel);
        $merged = array_merge($pbase, $prel);
        if ('/' != $prel['path'][0]) {
            // Relative path
            $dir = preg_replace('@/[^/]*$@', '', $pbase['path']);
            $merged['path'] = $dir . '/' . $prel['path'];
        }
        // Get the path components, and remove the initial empty one
        $pathParts = explode('/', $merged['path']);
        array_shift($pathParts);
        $path = [];
        $prevPart = '';
        foreach ($pathParts as $part) {
            if ('..' == $part && count($path) > 0) {
                // Cancel out the parent directory (if there's a parent to cancel)
                $parent = array_pop($path);
                // But if it was also a parent directory, leave it in
                if ('..' == $parent) {
                    array_push($path, $parent);
                    array_push($path, $part);
                }
            } elseif ('' != $prevPart || ('.' != $part && '' != $part)) {
                // Don't include empty or current-directory components
                if ('.' == $part) {
                    $part = '';
                }
                array_push($path, $part);
            }
            $prevPart = $part;
        }
        $merged['path'] = '/' . implode('/', $path);
        $ret = '';
        if (isset($merged['scheme'])) {
            $ret .= $merged['scheme'] . ':';
        }
        if (isset($merged['scheme']) || isset($merged['host'])) {
            $ret .= '//';
        }
        if (isset($prel['host'])) {
            $hostSource = $prel;
        } else {
            $hostSource = $pbase;
        }
        // username, password, and port are associated with the hostname, not merged
        if (isset($hostSource['host'])) {
            if (isset($hostSource['user'])) {
                $ret .= $hostSource['user'];
                if (isset($hostSource['pass'])) {
                    $ret .= ':' . $hostSource['pass'];
                }
                $ret .= '@';
            }
            $ret .= $hostSource['host'];
            if (isset($hostSource['port'])) {
                $ret .= ':' . $hostSource['port'];
            }
        }
        if (isset($merged['path'])) {
            $ret .= $merged['path'];
        }
        if (isset($prel['query'])) {
            $ret .= '?' . $prel['query'];
        }
        if (isset($prel['fragment'])) {
            $ret .= '#' . $prel['fragment'];
        }
        return $ret;
    }
}
