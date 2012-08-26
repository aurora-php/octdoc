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
     * Create documentation for a project and stream it to STDOUT.
     *
     * @octdoc      c:octdoc/doc
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class doc
    /**/
    {
        /**
         * Output format.
         *
         * @octdoc  p:doc/$format
         * @var     string
         */
        protected $format = 'htmlraw';
        /**/

        /**
         * Docblock definitions.
         *
         * @octdoc  p:doc/$docblock
         * @var     array
         */
        protected $docblock = array(
            '/**'   => array(
                        'start'  => '^\/\*\*',
                        'doc'    => '^\*',
                        'source' => array('^\*\/', false),
                        'end'    => '^\/\*\*\/'
                    ),
            '#**'   => array(
                        'start'  => '^#\*\*',
                        'doc'    => '^#',
                        'source' => array('^([^#]|$)', true),
                        'end'    => '^#\*\*'
                    ),
            '%**'   => array(
                        'start'  => '^%\*\*',
                        'doc'    => '^%',
                        'source' => array('^([^%]|$)', true),
                        'end'    => '^%\*\*'
                    )
        );
        /**/

        /**
         * Configuration of files to parse documentation in.
         *
         * @octdoc  p:doc/$extensions
         * @var     array
         */
        protected $files = array(
            '.+\.erl$', '.+\.php$', '.+\.js$', '.+\.css$', '^Makefile(|\..+)$'
        );
        /**/

        /**
         * Directories to scan for creating documentation.
         *
         * @octdoc  p:doc/$directories
         * @var     array
         */
        protected $directories = array(
            '^/'
        );
        /**/

        /**
         * Directories to skip when creating documentation.
         *
         * @octdoc  p:doc/$skip_directories
         * @var     array
         */
        protected $skip_directories = array(
            '/CVS/', '/\.svn/', '^/\.git/',
            '^/etc/',
            '^/data/',
            '^/tests?/',
            '^/tools/.*/app/', '^/tools/.*/data/', '^/tools/.*/libs/'
        );
        /**/

        /**
         * Extensions to strip from filenames.
         *
         * @octdoc  p:doc/$strip_extensions
         * @var     array
         */
        protected $strip_extensions = array(
            '\.class\.php$', '\.php$', '\.js$', '\.css$', '\.erl$'
        );
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:doc/__construct
         */
        public function __construct()
        /**/
        {
        }

        /**
         * Parse a file and extract it's documentation.
         *
         * @octdoc  m:doc/parse
         * @param   string                          $file           File to parse.
         * @return  bool|array                                      Returns false in case of an error or an array with the parsed documentation.
         */
        protected function parse($file)
        /**/
        {
            if (!is_readable($file) || !($fp = fopen($file, 'r'))) {
                return false;
            }

            $open = function($row) {
                foreach ($this->docblock as $tag => $def) {
                    if (preg_match('/' . $def['start'] . '/', $row)) {
                        return $tag;
                    }
                }

                return false;
            };
            $init = function() use ($file) {
                return array(
                    'file'       => $file,
                    'line'       => 0,
                    'scope'      => '',
                    'source'     => '',
                    'text'       => '',
                    'type'       => '',
                    'attributes' => array('param' => array())
                );
            };

            $opened = false;
            $source = false;
            $attrib = null;
            $tag    = false;
            $tmp    = $init();

            $return = array();
            $line   = 0;

            while (true) {
                $row = ltrim($raw = rtrim(fgets($fp, 4096)));
                $eof = feof($fp);

                ++$line;

                if (!$opened && !$eof) {
                    if (($tag = $open($row)) !== false) {
                        $opened = true;

                        $tmp['line'] = $line;
                    }
                } else {
                    if ($eof || preg_match('/' . $this->docblock[$tag]['end'] . '/', $row)) {
                        $opened = $source = $tag = false;
                        unset($attrib);

                        // TODO: output
                        if ($tmp['scope'] !== '') {
                            $return[] = $tmp;
                        }

                        $tmp = $init();

                        if ($eof) break;
                    } elseif ($source) {
                        $tmp['source'] .= $raw . "\n";
                    } elseif (preg_match('/' . $this->docblock[$tag]['source'][0] . '/', $row)) {
                        $source = true;
                        unset($attrib);

                        if ($this->docblock[$tag]['source'][1]) {
                            $tmp['source'] .= $raw . "\n";
                        }
                    } elseif (preg_match('/' . $this->docblock[$tag]['doc'] . '/', $row, $match)) {
                        $row = substr($row, strlen($match[0]));

                        if (preg_match('/ *@([a-z]+)/', $row, $match)) {
                            $row = trim(substr($row, strlen($match[0])));
                            unset($attrib);

                            switch ($match[1]) {
                            case 'octdoc':
                                if (preg_match('/^([a-z]):(.*)$/', $row, $match)) {
                                    $tmp['scope'] = $match[2];
                                    $tmp['type']  = $match[1];
                                }
                                break;
                            case 'param':
                                try {
                                    list($_type, $_name, $_text) = preg_split('/ +/', $row, 3);
                                } catch(\Exception $e) {
                                    \octdoc\stdlib::log('unable to parse @param in:', $tmp);
                                    continue;
                                }

                                $idx = count($tmp['attributes']['param']);

                                $tmp['attributes']['param'][$idx] = array(
                                    'name' => $_name,
                                    'type' => $_type,
                                    'text' => $_text
                                );

                                $attrib =& $tmp['attributes']['param'][$idx]['text'];
                                break;
                            case 'return':
                                try {
                                    list($_type, $_text) = preg_split('/ +/', $row, 2);
                                } catch(\Exception $e) {
                                    \octdoc\stdlib::log('unable to parse @return in:', $tmp);
                                    continue;
                                }

                                $tmp['attributes']['return'] = array(
                                    'type' => $_type,
                                    'text' => $_text
                                );
                                break;
                            default:
                                $tmp['attributes'][$match[1]] = $row . "\n";
                                $attrib =& $tmp['attributes'][$match[1]];
                                break;
                            }
                        } elseif (isset($attrib) !== false) {
                            $attrib .= $row . "\n";
                        } else {
                            $tmp['text'] .= $row . "\n";
                        }
                    }
                }
            }

            fclose($fp);

            return $return;
        }

        /**
         * Create organizational structure for documentation.
         *
         * @octdoc  m:doc/organize
         * @param   array                           $parts          Documentation parts to organize.
         * @return  array                                           Organized parts.
         */
        protected function organize(array $parts)
        /**/
        {
            // create tree structure of documentation
            $return = array();

            foreach ($parts as $part) {
                // sections
                $name    = basename($part['scope']);
                $scope   = explode('/', ltrim(dirname($part['scope']), '/'));
                $section = array_shift($scope);

                if (($part['sortkey'] = $part['scope'] = implode('/', $scope)) != '') {
                    $part['sortkey'] .= '/' . $name;
                } else {
                    $part['sortkey'] = $name;
                }

                if (!isset($return[$section])) $return[$section] = array();

                // type
                $type = $part['type'];

                if (!isset($return[$section][$type])) $return[$section][$type] = array();

                // part
                $return[$section][$type][] = $part;
            }

            // sort tree structure
            uksort($return, function($a, $b) {
                return (array_search($a, \octdoc\def::$sort['sections']) - array_search($b, \octdoc\def::$sort['sections']));
            });

            foreach ($return as &$types) {
                uksort($types, function($a, $b) {
                    return (array_search($a, \octdoc\def::$sort['indextypes']) - array_search($b, \octdoc\def::$sort['indextypes']));
                });

                foreach ($types as &$type) {
                    usort($type, function($a, $b) {
                        return strcmp($a['sortkey'], $b['sortkey']);
                    });
                }
            }

            return $return;
        }

        /**
         * Execute documentation creator.
         *
         * @octdoc  m:doc/exec
         */
        public function exec($src)
        /**/
        {
            $include = '(' . implode('|', $this->directories) . ')';
            $exclude = '(' . implode('|', $this->skip_directories) . ')';
            $files   = '(' . implode('|', $this->files) . ')';
            $strip   = '(' . implode('|', $this->strip_extensions) . ')';

            $parts = array();

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($src)
            );

            foreach ($iterator as $filename => $cur) {
                $path = preg_replace('|^' . $src . '|', '', $cur->getPathName());

                if (preg_match(':' . $include . ':', $path) && !preg_match(':' . $exclude . ':', $path) && preg_match(':' . $files . ':', basename($path))) {
                    // file to include in documentation
                    if (!($doc = $this->parse($cur->getPathName()))) {
                        continue;
                    }

                    $scope = dirname($path) . '/' . preg_replace('/' . $strip . '/', '', basename($path));
                    $name  = preg_replace('/[\/\.]/', '_', ltrim($scope, '/'));

                    if (!in_array($doc['0']['type'], array('h', 'c', 'i', 't'))) {
                        \octdoc\stdlib::log("first part in a file must be of type 'class', 'header', 'interface' or 'trait'", $doc[0]);

                        continue;
                    }

                    $parts[] = array(
                        'scope' => $scope,
                        'file'  => ($name = 'doc/' . $name . '.html'),
                        'type'  => $doc[0]['type'],
                        'name'  => $doc[0]['scope']
                    );

                    $this->write($name, $doc);
                }
            }

            $parts = $this->organize($parts);

            $this->index('doc/index.html', array(), $parts);
        }
    }
}
