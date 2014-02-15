<?php

/*
 * This file is part of octdoc
 * Copyright (C) 2012 by Harald Lapp <harald@octris.org>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This script can be found at:
 * https://github.com/aurora/octdoc
 */

namespace octdoc {
    /**
     * Registry.
     *
     * @octdoc      c:octdoc/registry
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class registry {
        /**
         * Storage for registered data.
         *
         * @octdoc  p:registry/$data
         * @type    array
         */
        protected static $data = array();
        /**/

        /**
         * Constructor and clone-method are private to keep the class static.
         *
         * @octdoc  m:registry/__construct, __clone
         */
        private function __construct() {}
        private function __clone() {}
        /**/

        /**
         * Store data in registry.
         *
         * @octdoc  m:registry/setValue
         * @param   string              $name                   Name to store value as.
         * @param   mixed               $value                  Value to store.
         */
        public static function setValue($name, $value)
        /**/
        {
            if (array_key_exists($name, self::$data)) {
                throw new \Exception(sprintf("There's already a value stored with the name '%s'", $name));
            } else {
                self::$data[$name] = $value;
            }
        }

        /**
         * Retrieve data from registry. Returns NULL, if entry is not set.
         *
         * @octdoc  m:registry/getValue
         * @param   string              $name                   Name of value to retrieve.
         * @return  mixed                                       Value or NULL
         */
        public static function getValue($name)
        /**/
        {
            return (array_key_exists($name, self::$data)
                    ? self::$data[$name]
                    : null);
        }
    }
}

