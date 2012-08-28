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

namespace octdoc\service {
    /**
     * Service for integrating pandoc.
     *
     * @octdoc      c:service/pandoc
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class pandoc extends \octdoc\service
    /**/
    {
        /**
         * Executable.
         *
         * @octdoc  p:hightlight/$cmd
         * @var     string
         */
        protected static $cmd = '';
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:pandoc/__construct
         */
        public function __construct()
        /**/
        {
            if (self::$cmd == '') {
                throw new \Exception("service 'pandoc' is not available!");
            }
        }

        /**
         * Test for possible requirements to support service.
         *
         * @octdoc  m:pandoc/test
         * @return  bool                                            Returns true, if format can be supported.
         */
        public static function test()
        /**/
        {
            self::$cmd = trim(`which pandoc`);

            return (self::$cmd != '');
        }

        /**
         * Execute service.
         *
         * @octdoc  m:pandoc/exec
         * @param   array                   $args                   Execution arguments.
         * @return  bool                                            Returns true, if format can be supported.
         */
        public function exec(array $args)
        /**/
        {
            $cmd = sprintf(
                self::$cmd . ' -f %s -t %s',
                escapeshellarg($args['from']),
                escapeshellarg($args['to'])
            );

            $ret = '';

            $pipe = new \octdoc\pipe($cmd);
            $pipe->write($args['text']);

            while (($row = $pipe->read()) !== false) {
                $ret .= $row;
            }

            $pipe->close();

            return $ret;
        }
    }
}
