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
     * Output handler base class.
     *
     * @octdoc      c:octdoc/output
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class output
    /**/
    {
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
         * Return array of available output types.
         *
         * @octdoc  m:output/getTypes
         * @return  array                                       List of output types.
         */
        public static function getTypes()
        /**/
        {
            return array_map(function($v) {
                return basename($v, '.class.php');
            }, glob(__DIR__ . '/output/*.class.php'));
        }

        /**
         * Return new instance of a specified output handler.
         *
         * @octdoc  m:output/getInstance
         * @param   string              $type                   Name of output handler.
         * @param   string              $output                 Optional destination for output.
         * @return  \octdoc\format                              Instance of output handler.
         */
        public static function getInstance($type, $output = 'php://stdout')
        /**/
        {
            $file  = $type . '.class.php';
            $class = '\\octdoc\\output\\' . $type;

            if (!file_exists(__DIR__ . '/output/' . $file)) {
                die(sprintf("unable to load output handler '%'\n", $type));
            }

            return new $class($output);
        }

        /**
         * Add file to output.
         *
         * @octdoc      a:output/addFile
         * @param       string          $filename               Name of file to add.
         * @param       string          $data                   Data to write.
         */
        abstract public function addFile($filename, $data);
        /**/

        /**
         * Add directory to output
         *
         * @octdoc      a:output/addDirectory
         * @param       string          $directory              Name of directory to add.
         */
        abstract public function addDirectory($directory);
        /**/
    }
}
