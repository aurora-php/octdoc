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
     * Service base class.
     *
     * @octdoc      c:octdoc/service
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    abstract class service
    /**/
    {
        /**
         * Supported services.
         *
         * @octdoc  p:service/$services
         * @var     array
         */
        protected static $services = null;
        /**/

        /**
         * Instances of service classes.
         *
         * @octdoc  p:service/$instances
         * @var     array
         */
        protected static $instances = array();
        /**/

        /**
         * Initialize, test which services are supported.
         *
         * @octdoc  m:service/init
         */
        protected static function init()
        /**/
        {
            self::$services = array();

            foreach (new \DirectoryIterator(__DIR__ . '/service/') as $file) {
                if ($file->isFile() && preg_match('/\.class\.php$/', $name = $file->getFilename())) {
                    $service = basename($name, '.class.php');
                    $class   = '\\octdoc\\service\\' . $service;

                    if ($class::test()) {
                        self::$services[] = $service;
                    }
                }
            }
        }

        /**
         * Return list of supported services.
         *
         * @octdoc  m:service/getServices
         */
        public static function getServices()
        /**/
        {
            if (is_null(self::$services)) {
                self::init();
            }

            return self::$services;
        }

        /**
         * Get an instance of the specified service.
         *
         * @octdoc  m:service/getInstance
         * @param   string          $service                Name of service to return instance of.
         * @return  \octdoc\service                         Instance of service.
         */
        public static function getInstance($service)
        /**/
        {
            if (!isset(self::$instances[$service])) {
                $class = '\\octdoc\\service\\' . $service;

                self::$instances[$service] = new $class();
            }

            return self::$instances[$service];
        }

        /**
         * Get a service callback or return specified fallback, if a service is not available.
         *
         * @octdoc  m:service/callService
         * @param   string          $service                Name of service to call.
         * @param   callable        $fallback               Fallback to call.
         * @return  callable                                Callback to use.
         */
        public static function getCallback($service, callable $fallback)
        /**/
        {
            if (!in_array($service, self::$services)) {
                $return = $fallback;
            } else {
                $instance = self::getInstance($service);

                $return = function(array $args = array()) use ($instance) {
                    return $instance->exec($args);
                };
            }

            return $return;
        }

        /**
         * Test for possible requirements to support service.
         *
         * @octdoc  m:service/test
         * @return  bool                                            Returns true, if format can be supported.
         */
        abstract public static function test();
        /**/

        /**
         * Execute service.
         *
         * @octdoc  m:service/exec
         * @param   array                   $args                   Execution arguments.
         * @return  bool                                            Returns true, if format can be supported.
         */
        abstract public function exec(array $args);
        /**/
    }
}
