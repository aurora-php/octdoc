<?php

/*
 * This file is part of octdoc
 * Copyright (C) 2011 by Harald Lapp <harald@octris.org>
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
     * Standard library for CLI applications.
     *
     * @octdoc      c:libs/stdlib
     * @copyright   copyright (c) 2011 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class stdlib
    /**/
    {
        /**
         * Option settings.
         *
         * @octdoc  d:stdlib/T_OPT_OPTIONAL, T_OPT_REQUIRED, T_OPT_MULTIPLE
         */
        const T_OPT_OPTIONAL = 0;
        const T_OPT_REQUIRED = 1;       // whether the option is required.
        const T_OPT_NTIMES   = 2;       // whether the option may be specified multiple times.
        const T_OPT_NVALUES  = 4;       // TODO: whether the option may have multiple values.
        /**/

        /**
         * Output message to STDERR.
         *
         * @octdoc  m:stdlib/log
         * @param   string                          $msg            Message to output.
         * @param   array                           $payload        Optional additional information to output.
         */
        public static function log($msg, array $payload = null)
        /**/
        {
            fputs(STDERR, trim($msg) . "\n");

            if (!is_null($payload)) {
                fputs(STDERR, sprintf("  file: %s\n", $payload['file']));
                fputs(STDERR, sprintf("  line: %s\n", $payload['line']));
            }

            fputs(STDERR, "\n");
        }

        /**
         * Parse command line options and return Array of them. The parameters are required to have
         * the following format:
         *
         * - short options: -l -a -b
         * - short options combined: -lab
         * - short options with value: -l val -a val -b "with whitespace"
         * - long options: --option1 --option2
         * - long options with value: --option=value --option value --option "with whitespace"
         *
         * This implementation of supports
         *
         * @octdoc  m:cli/getOptions
         * @param   array       $options                Optional array to define expected options and
         *                                              and option settings.
         * @param   array       $missing                Optional array returns missing options, if they
         *                                              have been defined through the options parameter
         *                                              with the T_OPT_REQUIRED setting.
         * @return  array                               Parsed command line parameters.
         */
        public static function getOptions(array $options = array(), array &$missing = array())
        /**/
        {
            global $argv;
            static $opts = null;

            if (is_array($opts)) {
                // already parsed
                return $opts;
            }

            // TODO: change hardcoded flags to class constants, as soon as PHP5.4 is stable
            $add_opt = function($new_opt) use (&$opts, &$options) {
                $key = '';

                if (!$options) {
                    $opts = array_merge($new_opt, $opts);
                    $key  = key($new_opt);
                } else {
                    foreach ($new_opt as $k => $v) {
                        if (!array_key_exists($k, $options)) continue;                  // skip: not defined as allowed

                        $opts[$key = $k] = (isset($opts[$k]) && (($options[$k] & 2) == 2)
                                            ? $opts[$k]
                                            : $v);
                    }
                }

                return (count($new_opt) > 1
                        ? false
                        : $key);
            };

            $args = $argv;
            $opts = array();
            $key  = '';
            $idx  = 1;

            $def  = (count($options) > 0);

            array_shift($args);

            foreach ($args as $arg) {
                if (preg_match('/^-([a-zA-Z]+)$/', $arg, $match)) {
                    // short option, combined short options
                    $tmp  = str_split($match[1], 1);
                    $key = $add_opt(array_combine($tmp, array_fill(0, count($tmp), true)));
                    continue;
                } elseif (preg_match('/^--([a-zA-Z][a-zA-Z0-9]+)(=.*|)$/', $arg, $match)) {
                    // long option
                    $key = $add_opt(array($match[1] => true));

                    if (strlen($match[2]) == 0) {
                        continue;
                    }

                    $arg = substr($match[2], 1);
                } elseif (strlen($arg) > 1 && substr($arg, 0, 1) == '-') {
                    // invalid option format
                    throw new \Exception('invalid option format "' . $arg . '"');
                }

                if ($key === '') {
                    // no option name, add as numeric option
                    $opts[$idx++] = $arg;
                } elseif ($key !== false) {
                    if (!is_bool($opts[$key])) {
                        // multiple values for this option
                        if (!is_array($opts[$key])) {
                            $opts[$key] = array($opts[$key]);
                        }

                        $opts[$key][] = $arg;
                    } else {
                        $opts[$key] = $arg;
                    }
                }
            }

            // determine missing required options
            foreach ($options as $o => $s) {
                if ($s & self::T_OPT_REQUIRED === self::T_OPT_REQUIRED && !isset($opts[$o])) {
                    $missing[] = $o;
                }
            }

            return $opts;
        }
    }
}
