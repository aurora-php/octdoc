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
     * Class Autoloader.
     *
     * @octdoc      c:octdoc/autoloader
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class autoloader
    /**/
    {
        /**
         * Class Autoloader.
         *
         * @octdoc  m:autoloader/autoload
         * @param   string      $classpath      Path of class to load.
         */
        public static function autoload($classpath)
        /**/
        {
            $pkg = preg_replace('|\\\\|', '/', str_replace('octdoc\\', '', ltrim($classpath, '\\\\'))) . '.class.php';

            try {
                include_once(__DIR__ . '/' . $pkg);
            } catch(\Exception $e) {
            }
        }
    }

    spl_autoload_register(array('\octdoc\autoloader', 'autoload'));
}