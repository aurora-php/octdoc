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
     * Utility class for creating 'tar' output.
     *
     * @octdoc      c:octdoc/tar
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class tar
    /**/
    {
        /**
         * File handle.
         *
         * @octdoc      v:tar/$fh
         * @var         resource
         */
        protected $fh = null;
        /**/

        /**
         * Directories already created in tar.
         *
         * @octdoc      v:tar/$directories
         * @var         array
         */
        protected $directories = array();
        /**/

        /**
         * User information.
         *
         * @octdoc      v:tar/$info
         * @var         array
         */
        protected $info = array();
        /**/

        /**
         * Constructor.
         *
         * @octdoc      m:tar/__construct
         * @param       string          $output                 Destination to output tar to.
         */
        public function __construct($output = 'php://stdout')
        /**/
        {
            if (($tmp = fopen($output, 'w'))) {
                $this->fh = $tmp;
            }

            $user_info  = posix_getpwuid($uid = posix_getuid());
            $group_info = posix_getgrgid($gid = posix_getgid());

            $this->info = array(
                'user_id'    => $uid,
                'group_id'   => $gid,
                'user_name'  => $user_info['name'],
                'group_name' => $group_info['name']
            );
        }

        /**
         * Destructor.
         *
         * @octdoc      m:tar/__deconstruct
         */
        public function __destruct()
        /**/
        {
            if (!is_null($this->fh)) {
                fclose($this->fh);
            }
        }

        /**
         * Write header to output.
         *
         * @octdoc      m:tar/writeHeader
         * @param       string          $name                   Name of file or directory.
         * @param       int             $content_length         Optional length of content.
         */
        protected function writeHeader($name, $content_length = 0)
        /**/
        {
            $is_dir = (substr($name, -1) == '/');

            // create header
            $header =
                str_pad($name, 100, chr(0)) .
                str_pad(($is_dir ? '755' : '644'), 7, '0', STR_PAD_LEFT) . chr(0) .
                str_pad(decoct($this->info['user_id']), 7, '0', STR_PAD_LEFT) . chr(0) .
                str_pad(decoct($this->info['group_id']), 7, '0', STR_PAD_LEFT) . chr(0) .
                str_pad(decoct($content_length), 11, '0', STR_PAD_LEFT) . chr(0) .
                str_pad(decoct(time()), 11, '0', STR_PAD_LEFT) . chr(0) .
                str_repeat(' ', 8) .
                ($is_dir ? '5' : '0') .
                str_repeat(chr(0), 100) .
                'ustar' . chr(0) . '00' .
                str_pad($this->info['user_name'], 32, chr(0)) .
                str_pad($this->info['group_name'], 32, chr(0)) .
                str_repeat(chr(0), 8) .
                str_repeat(chr(0), 8) .
                str_repeat(chr(0), 155);

            $header = str_pad($header, 512, chr(0));

            // calculate checksum
            $chksum = 0;

            for ($i = 0; $i < 512; ++$i) {
                $chksum += ord($header[$i]);
            }

            $chksum = substr(str_pad(decoct($chksum), 6, '0', STR_PAD_LEFT), 0, 6);

            // merge header with checksum and output header
            fputs($this->fh, substr_replace($header, $chksum . chr(0) . chr(32), 148, 8));
        }

        /**
         * Add file to output.
         *
         * @octdoc      m:tar/addFile
         * @param       string          $filename               Name of file to add.
         * @param       string          $data                   Data to write.
         */
        public function addFile($filename, $data)
        /**/
        {
            $dir = ltrim(dirname($filename), '/');

            if ($dir != '' && array_search($dir, $this->directories) === false) {
                $this->addDirectory($dir);
            }

            $this->writeHeader($filename, $len = strlen($data));

            fputs($this->fh, str_pad($data, (ceil($len / 512) * 512), chr(0)));
        }

        /**
         * Add directory to output
         *
         * @octdoc      m:tar/addDirectory
         * @param       string          $directory              Name of directory to add.
         */
        public function addDirectory($directory)
        /**/
        {
            $tmp = explode('/', trim($directory, '/'));

            for ($i = 0, $cnt = count($tmp); $i < $cnt; ++$i) {
                $dir = implode('/', array_slice($tmp, 0, $i + 1)) . '/';

                if (array_search($dir, $this->directories) === false) {
                    $this->writeHeader($dir);

                    $this->directories[] = $dir;
                }
            }
        }
    }
}
