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
 * @category Model
 * @package  ApiCore
 * @author   Jan Malčák <jan@malcak.cz>
 * @license  MIT https://opensource.org/licenses/MIT
 * @link     https://github.com/malja/ApiCore
 */

namespace malja\ApiCore;

use \malja\ApiCore\Schema\Schema;
use \malja\ApiCore\Schema\ValidationException;
use \PicORM\Model as BaseModel;

/**
 * Model is a ORM representation of MySQL table. It is using PicORM under the
 * hood for all the "hard work".
 *
 * There are two ways of defining a model. First one is to define all fields
 * required by PicORM - $_tableName, $_tableFields and $_primaryKey. And if
 * you want validation $_rules. This means writing the same information multiple
 * times.
 *
 * The other way is to define public properties with corresponding comments
 * and call ::init() function.
 *
 * @see init()
 */
class Model extends BaseModel
{

    /**
     * Rules for data validation of this model.
     * @link https://github.com/vanilla/garden-schema
     */
    protected static $_rules = [];

    /**
     * Validate data in array with schema given in `$this->rules`.
     * @param array $data   Array with data.
     * @param bool $ignore_primary Ignore primary key rules.
     * @param bool $sparse Sparse parsing will ignore missing keys.
     * @return \malja\ApiCore\Schema\Validation|bool Validation object on fail, true otherwise.
     * @throws \malja\ApiCore\Schema\ValidationException
     * @link https://github.com/vanilla/garden-schema
     */
    public static function validateArray(array $data, bool $ignore_primary = false, bool $sparse = false)
    {
        $final_rules = [];

        // Remove primary key for $_rules
        if ($ignore_primary) {

            // Get primary key name
            $primary_key = static::$_primaryKey;

            // Search primary key in rules
            foreach (static::$_rules as $key => $value) {

                // If rule is an array
                if (is_array($value)) {
                    // Rule name is in the key
                    $ruleName = $key;
                } else {
                    // Rule name is in the value
                    $ruleName = $value;
                }

                // Search primary key name in rule name
                // And ignore it.
                if (!preg_match("/^" . $primary_key . "([:\?]|$)/", $ruleName)) {
                    $final_rules[$key] = $value;
                }
            }
        } else {
            $final_rules = static::$_rules;
        }

        $schema = Schema::parse($final_rules);

        try {
            $schema->validate($data, $sparse);
            return true;
        } catch (ValidationException $e) {
            return $e->getValidation();
        }
    }

    /**
     * Validate current content of model against rules given in `$this->rules`.
     *
     * @param bool $ignore_primary Ignore rules for primary key.
     * @param bool $sparse Sparse validation will ignore missing keys.
     * @return bool|string Validity or error message.
     * @throws \malja\ApiCore\Schema\ValidationException
     * @see isValid()
     * @see validateArray()
     */
    public function validate($ignore_primary = false, $sparse = false)
    {
        return static::validateArray($this->toArray(), $ignore_primary, $sparse);
    }

    /**
     * Validate current content of model. In contrast to validate() method,
     * this one does not throw anything. Only result is returned boolean.
     *
     * @return bool Is content valid for this model?
     *
     * @link https://github.com/vanilla/garden-schema
     * @see validate()
     */
    public function isValid()
    {
        $data = $this->toArray();
        $schema = Schema::parse(static::$_rules);
        return $schema->isValid($data);
    }

    /**
     * Validates data before returned. This ensures all types do
     * correspond.
     * @param bool $includePrimary  Include or not primary key in returned array
     * @return array Array with correct types or empty array.
     */
    public function toArray($includePrimary = true)
    {
        $data = parent::toArray($includePrimary);

        $schema = Schema::parse(static::$_rules);

        // Get validation error exception
        return $schema->validate($data);
    }

    /**
     * Create model instance from array.
     *
     * **Note**: It has the same effect as `hydrate()` method, except it sets model in "new" state and thus when saving,
     * `insert` SQL statement is used rather than `update`. With each insert operation, primary key is filled with new
     * value.
     *
     * **Warning**: Data is not checked before assigned. Validate $data array with `validateArray()` method, or call
     * `isValid()` on created instance.
     *
     * @param array $data Data for model creation.
     * @return object New model instance.
     * @see hydrate()
     */
    public static function fromArray(array $data)
    {
        $model = new static;
        $model->hydrate($data);
        $model->_isNew = true;
        return $model;
    }
}
