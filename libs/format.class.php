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
     * Format handler main class.
     *
     * @octdoc      c:octdoc/format
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class format
    /**/
    {
        /**
         * Stores a map of the supported formats and the class implementing a format.
         *
         * @octdoc  p:format/$formats
         * @type    array
         */
        private static $formats = array();
        /**/

        /**
         * Documentation title.
         *
         * @octdoc  p:format/$title
         * @type    string                                      Documentation title.
         */
        protected $title = '';
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:octdoc/format
         */
        protected function __construct()
        /**/
        {
        }

        /**
         * Return array of available format types.
         *
         * @octdoc  m:format/getTypes
         * @return  array                                       List of format types.
         */
        public static function getTypes()
        /**/
        {
            if (count(self::$formats) == 0) {
                foreach (new \DirectoryIterator(__DIR__ . '/format/') as $file) {
                    if ($file->isFile() && preg_match('/\.class\.php$/', $name = $file->getFilename())) {
                        $type  = basename($name, '.class.php');
                        $class = '\\octdoc\\format\\' . $type;

                        foreach ($class::getFormats() as $format) {
                            self::$formats[$format] = $type;
                        }
                    }
                }
            }

            return array_keys(self::$formats);
        }

        /**
         * Return new instance of a specified format handler.
         *
         * @octdoc  m:format/getInstance
         * @param   string              $format                 Name of format to return instance of handler for.
         * @param   \octdoc\output      $output                 Output handler to use.
         * @return  \octdoc\format                              Instance of format handler.
         */
        public static function getInstance($format, \octdoc\output $output)
        /**/
        {
            if (!isset(self::$formats[$format])) {
                \octdoc\stdlib::log(sprintf("unsupported format '%s'", $format));

                die(1);
            }

            $file  = self::$formats[$format] . '.class.php';
            $class = '\\octdoc\\format\\' . self::$formats[$format];

            return new $class($output);
        }

        /**
         * Return the formats that are supported by the implemnenting class.
         *
         * @octdoc  m:format/getFormats
         * @return  array                           Array with strings of the formats.
         */
        public static function getFormats()
        /**/
        {
            return array();
        }

        /**
         * Preprocess. This method get's called before the first page is written.
         *
         * @octdoc  m:format/preprocess
         */
        public function preprocess()
        /**/
        {
        }

        /**
         * Preprocess. This method get's called after all pages and the index page have been written.
         *
         * @octdoc  m:format/preprocess
         */
        public function postprocess()
        /**/
        {
        }

        /**
         * Write documentation index to temporary directory.
         *
         * @octdoc  m:format/index
         * @param   string                          $file           File to write index into.
         * @param   array                           $doc            Generic module documentation.
         * @param   array                           $source         Documentation parts extracted from source code.
         */
        abstract public function index($file, array $doc, array $source);
        /**/

        /**
         * Write documentation for a specified file.
         *
         * @octdoc  m:format/page
         * @param   string                          $file           File to write documentation into.
         * @param   string                          $title          Page title.
         * @param   array                           $doc            Documentation to write.
         */
        abstract public function page($file, $title, array $doc);
        /**/
    }
}