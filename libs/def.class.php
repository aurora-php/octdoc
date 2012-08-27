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
     * Common definitions.
     *
     * @octdoc      c:octdoc/def
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class def
    /**/
    {
        /**
         * Docblock types.
         *
         * @octdoc  p:def/$types
         * @var     array
         */
        public static $types = array(
            'c' => 'Class',
            'd' => 'Constant',
            'f' => 'Function',
            'h' => 'Other',
            'i' => 'Interface',
            'l' => 'License',
            'm' => 'Method',
            'p' => 'Property',
            't' => 'Trait',
            'v' => 'Variable'
        );
        /**/

        /**
         * Docblock attributes.
         *
         * @octdoc  p:def/$attributes
         * @var     array
         */
        public static $attributes = array(
            'author'    => 'Author',
            'copyright' => 'Copyright',
            'license'   => 'License',
            'package'   => 'Package',

        );
        /**/

        /**
         * Documentation sections.
         *
         * @octdoc  p:def/$sections
         * @var     array
         */
        public static $sections = array(
            'libs'     => 'Libraries',
            'libsjs'   => 'Javascript',
            'src'      => 'Sources',
            'includes' => 'Includes',
            'styles'   => 'Stylesheets',
            'tools'    => 'Tools',
            ''         => 'Misc'
        );
        /**/

        /**
         * Sorting criteria configuration.
         *
         * @octdoc  p:def/$sort
         * @var     array
         */
        public static $sort = array(
            'types' => array(
                'h' => 0,
                'l' => 1,
                'v' => 2,
                'f' => 3,
                'c' => 4, 'i' => 4, 't' => 4,
                'd' => 5,
                'p' => 6,
                'm' => 7
            ),
            'attributes' => array(
                'package',
                'license',
                'copyright',
                'author',
                'extends',
                'deprecated',
                'since',
                'see',
                'tutorial',
                'example',
                'abstract',
                'static',
                'param',
                'return',
                'todo'
            ),
            'sections' => array(
                'libs', 'libsjs', 'styles', 'tools', 'src', 'includes'
            ),
            'indextypes' => array(
                'h', 'c', 't', 'i'
            )
        );
        /**/

        /**
         * Documentation depth.
         *
         * @octdoc  p:def/$depth
         * @var     array
         */
        public static $depth = array(
            'h' => 1,
            'v' => 1, 'f' => 1,
            'c' => 1, 'i' => 1, 't' => 1,
            'd' => 3, 'p' => 3, 'm' => 3,

            'scope' => 5
        );
        /**/

        /**
         * Number of maximum lines to include in source block.
         *
         * @octdoc  p:def/$source_lines
         * @var     int
         */
        public static $source_lines = 9;
        /**/
    }
}