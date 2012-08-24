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
    require_once(__DIR__ . '/stdlib.class.php');
    require_once(__DIR__ . '/doc.class.php');
    require_once(__DIR__ . '/pipe.class.php');

    /**
     * Main application class for octdoc.
     *
     * @octdoc      c:libs/main
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class main
    /**/
    {
        /**
         * Constructor.
         *
         * @octdoc  m:main/__construct
         */
        public function __construct()
        /**/
        {
        }

        /**
         * Execute application.
         *
         * @octdoc  m:main/run
         */
        public function run()
        /**/
        {
            global $argv;

            // parse command-line arguments
            $missing = array();
            $options = stdlib::getOptions(array(
                'i' => stdlib::T_OPT_REQUIRED
            ), $missing);

            if (count($missing)) {
                die(sprintf("usage: %s -i input-directory\n", $argv[0]));
            }

            if (!is_dir($options['i'])) {
                die("no directory specified\n");
            } else {
                $inp = $options['i'];
            }

            $doc = new \octdoc\doc();
            $doc->exec($inp);
        }
    }
}
