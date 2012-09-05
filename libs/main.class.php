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
    require_once(__DIR__ . '/autoloader.class.php');

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

            $formats = \octdoc\format::getTypes();
            $targets = \octdoc\output::getTypes();

            // parse command-line arguments
            $missing = array();
            $options = stdlib::getOptions(array(
                'i'    => stdlib::T_OPT_REQUIRED,
                'f'    => stdlib::T_OPT_OPTIONAL,
                't'    => stdlib::T_OPT_OPTIONAL,
                'h'    => stdlib::T_OPT_OPTIONAL,
                'help' => stdlib::T_OPT_OPTIONAL
            ), $missing);

            if (isset($options['h']) || isset($options['help'])) {
                print "octris documentation extractor\n";
                print "copyright (c) 2012 by Harald Lapp <harald@octris.org>\n\n";
                printf("usage: %s -h\n", $argv[0]);
                printf("usage: %s -i input-directory [-f output-format] [-t output-target]\n\n", $argv[0]);
                print "options\n\n";
                print "    -i input-directory\n";
                print "    -f output-format\n";
                print "    -t output-target\n\n";
                print "formats\n\n";
                print "    " . implode("\n    ", $formats) . "\n\n";
                print "targets\n\n";
                print "    " . implode("\n    ", $targets) . "\n\n";
                die(0);
            }

            if (count($missing)) {
                printf("usage: %s -h\n", $argv[0]);
                printf("usage: %s -i input-directory [-f output-format] [-t output-target]\n", $argv[0]);
                die(0);
            }

            // input directory
            if (!is_dir($options['i'])) {
                die("no directory specified\n");
            } else {
                $inp = $options['i'];
            }

            // output format
            if (isset($options['f'])) {
                if (!in_array($options['f'], $formats)) {
                    die(sprintf("unknown output format '%s'\n", $options['f']));
                } else {
                    $fmt = $options['f'];
                }
            } else {
                $fmt = 'htmlraw';
            }

            // output target
            if (isset($options['t'])) {
                if (!in_array($options['t'], $targets)) {
                    die(sprintf("unknown output target '%s'\n", $options['t']));
                } else {
                    $out = $options['t'];
                }
            } else {
                $out = 'tar';
            }

            $doc = new \octdoc\doc();
            $doc->setFormat($fmt);
            $doc->setOutput($out);
            $doc->exec($inp);
        }
    }

    // register error handler for 'normal' php errors
    set_error_handler(function($code, $msg, $file, $line) {
        throw new \ErrorException($msg, $code, 0, $file, $line);
    }, E_ALL);
}
