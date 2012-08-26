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
         * Docblock types.
         *
         * @octdoc  p:doc/$types
         * @var     array
         */
        protected $types = array(
            'c' => 'Class',
            'd' => 'Constant',
            'f' => 'Function',
            'h' => 'Header',
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
         * @octdoc  p:doc/$attributes
         * @var     array
         */
        protected $attributes = array(
            'author'    => 'Author',
            'copyright' => 'Copyright',
            'license'   => 'License',
            'package'   => 'Package',

        );
        /**/

        /**
         * Documentation sections.
         *
         * @octdoc  p:doc/$sections
         * @var     array
         */
        protected $sections = array(
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
         * @octdoc  p:doc/$sort
         * @var     array
         */
        protected $sort = array(
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
         * Number of maximum lines to include in source block.
         *
         * @octdoc  p:doc/$source_lines
         * @var     int
         */
        protected $source_lines = 9;
        /**/

        /**
         * Documentation depth.
         *
         * @octdoc  p:doc/$depth
         * @var     array
         */
        protected $depth = array(
            'h' => 1,
            'v' => 1, 'f' => 1,
            'c' => 1, 'i' => 1, 't' => 1,
            'd' => 3, 'p' => 3, 'm' => 3,

            'scope' => 5
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
         * Instance of tar class.
         *
         * @octdoc  p:doc/$tar
         * @var     \octdoc\tar
         */
        protected $tar = null;
        /**/

        /**
         * Instance of text formatter.
         *
         * @octdoc  p:doc/$formatter
         * @var     \octdoc\formatter
         */
        protected $formatter = null;
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
         * Return instance of tar class.
         *
         * @octdoc  m:doc/getTar
         * @return  \octdoc\tar                             Instance of tar class.
         */
        protected function getTar()
        /**/
        {
            if (is_null($this->tar)) {
                $this->tar = new \octdoc\tar();
            }

            return $this->tar;
        }

        /**
         * Return instance of text formatter.
         *
         * @octdoc  m:doc/getFormatter
         * @return  \octdoc\format                          Instance of formatter class.
         */
        protected function getFormatter()
        /**/
        {
            if (is_null($this->formatter)) {
                $this->formatter = new \octdoc\format();
            }

            return $this->formatter;
        }

        /**
         * Output message to STDERR.
         *
         * @octdoc  m:doc/log
         * @param   string                          $msg            Message to output.
         * @param   array                           $payload        Optional additional information to output.
         */
        protected function log($msg, array $payload = null)
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
                                    $this->log('unable to parse @param in:', $tmp);
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
                                    $this->log('unable to parse @return in:', $tmp);
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
         * Write documentation index to temporary directory.
         *
         * @octdoc  m:doc/index
         * @param   string                          $file           File to write index into.
         * @param   array                           $doc            Generic module documentation.
         * @param   array                           $source         Documentation parts extracted from source code.
         */
        protected function index($file, array $doc, array $source)
        /**/
        {
            if (!($fp = fopen('php://memory', 'w'))) {
                $this->log("unable to open file '$file' for writing");
                return false;
            }

            fputs($fp, "<h1>Index</h1>\n");

            // generic documentation index
            if (count($doc) > 0) {
                fputs($fp, "<h2>Documentation</h2>\n");

                // TODO
            }

            // API documentation index
            /* create tree from flat array */
            $getTree = function(array $files) {
                $tree = array();

                foreach ($files as $file) {
                    $node  =& $tree;

                    if ($file['scope'] !== '') {
                        $scope = explode('/', $file['scope']);

                        foreach ($scope as $s) {
                            if (!array_key_exists($s, $node)) $node[$s] = array();

                            $node =& $node[$s];
                        }
                    }

                    $node[] = $file;
                }

                return $tree;
            };

            /* write list to file */
            $putList = function(array $tree, $level = 0) use ($fp, &$putList) {
                fputs($fp, "<ul>\n");

                array_walk($tree, function($node, $key) use ($fp, &$tree, $putList) {
                    static $li = true;

                    next($tree);

                    if (is_int($key)) {
                        fputs($fp, sprintf('<li><a href="%s">%s</a>', basename($node['file']), htmlentities($node['name'])));

                        if (!is_string(key($tree))) fputs($fp, '</li>');

                        $li = false;
                    } elseif (count($node) > 0) {
                        if ($li) {
                            fputs($fp, '<li>' . $key);
                        }

                        $putList($node);

                        fputs($fp, "</li>\n");

                        $li = true;
                    }
                });

                fputs($fp, "</ul>\n");
            };

            foreach ($source as $section => $part) {
                // section header
                if (!isset($this->section[$section])) $section = '';

                fputs($fp, sprintf("<h2>%s</h2>\n", $this->sections[$section]));

                foreach ($part as $type => $files) {
                    if (count($files) == 0) continue;

                    // type header
                    fputs($fp, sprintf("<h3>%s</h3>\n", $this->types[$type]));

                    if (count(($tree = $getTree($files))) > 0) {
                        $putList($tree);
                    }
                }
            }

            rewind($fp);

            $this->getTar()->addFile($file, stream_get_contents($fp));

            fclose($fp);
        }

        /**
         * Write documentation to temporary directory.
         *
         * @octdoc  m:doc/write
         * @param   string                          $file           File to write documentation into.
         * @param   array                           $doc            Documentation to write.
         */
        protected function write($file, array $doc)
        /**/
        {
            if (!($fp = fopen('php://memory', 'w'))) {
                $this->log("unable to open file '$file' for writing");
                return false;
            }

            $type = '';

            foreach ($doc as $part) {
                // write a section header
                if ($type != $part['type']) {
                    $type = $part['type'];

                    fputs($fp, sprintf("<h%1\$d>%2\$s</h%1\$d>\n", $this->depth[$type], htmlentities($this->types[$type])));
                }

                if (($pos = strpos($part['scope'], '/')) !== false) {
                    fputs($fp, sprintf(
                        "<h%1\$d>%2\$s</h%1\$d>\n",
                        $this->depth['scope'],
                        substr($part['scope'], $pos + 1)
                    ));
                }

                // write description
                if (trim($part['text']) != '') {
                    fputs($fp, $this->getFormatter()->process($part['text']));
                }

                // write included source code
                if (trim($tmp = $part['source']) != '') {
                    // cut preceeding spaces but keep indentation
                    if (preg_match('/^( +)/', $tmp, $match)) {
                        $tmp = preg_replace('/^' . $match[1] . '/m', '', $tmp);
                    }

                    // renove trailing spaces and cut off last newline
                    $tmp = preg_replace('/[ ]+$/m', '', rtrim($tmp));

                    // remove source lines, if there are too much
                    if (substr_count($tmp, "\n") > $this->source_lines) {
                        $tmp = preg_split("/\n/", $tmp, $this->source_lines + 1);
                        $tmp = array_reverse($tmp);
                        array_shift($tmp);

                        while (count($tmp) > 0 && trim($tmp[0]) === '') {
                            array_shift($tmp);
                        }

                        if (count($tmp) > 0) {
                            preg_match('/^[ ]*/', $tmp[0], $match);

                            $tmp = array_reverse($tmp);

                            $tmp[] = $match[0] . '...';
                            $tmp = implode("\n", $tmp);
                        } else {
                            $tmp = '';
                        }

                    }

                    if ($tmp != '') fputs($fp, sprintf("<pre>%s</pre>\n", htmlentities($tmp)));
                }

                // write additional attributes
                fputs($fp, "<dl>\n");

                foreach ($this->sort['attributes'] as $name) {
                    if (!isset($part['attributes'][$name])) continue;

                    $attr =& $part['attributes'][$name];

                    if (is_array($attr) && count($attr) == 0) continue;

                    $dd = '';

                    switch ($name) {
                    case 'deprecated':
                        $dd .= "Yes";
                        break;
                    case 'param':
                        $dd .= "<table width=\"100%\"><thead><tr>\n";
                        $dd .= "<th>Name</th><th>Type</th><th>Description</th>\n";
                        $dd .= "</tr></thead><tbody>\n";

                        foreach ($attr as $r) {
                            $dd .= sprintf(
                                "<tr><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                                $r['name'], $r['type'], $r['text']
                            );
                        }

                        $dd .= "</tbody></table>\n";
                        break;
                    case 'return':
                        $dd .= "<table width=\"100%\"><thead><tr>\n";
                        $dd .= "<th>Type</th><th>Description</th>\n";
                        $dd .= "</tr></thead><tbody>\n";

                        $dd .= sprintf(
                            "<tr><td>%s</td><td>%s</td></tr>\n",
                            $attr['type'], $attr['text']
                        );

                        $dd .= "</tbody></table>\n";
                        break;
                    default:
                        $dd = $attr;
                        break;
                    }

                    if ($dd) {
                        fputs($fp, sprintf("<dt>%s</dt>\n", htmlentities($name)));
                        fputs($fp, sprintf("<dd>%s</dd>\n", $dd));
                    }
                }

                fputs($fp, "</dl>\n");
            }

            rewind($fp);

            $this->getTar()->addFile($file, stream_get_contents($fp));

            fclose($fp);

            return true;
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
                return (array_search($a, $this->sort['sections']) - array_search($b, $this->sort['sections']));
            });

            foreach ($return as &$types) {
                uksort($types, function($a, $b) {
                    return (array_search($a, $this->sort['indextypes']) - array_search($b, $this->sort['indextypes']));
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
                        $this->log("first part in a file must be of type 'class', 'header', 'interface' or 'trait'", $doc[0]);

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
