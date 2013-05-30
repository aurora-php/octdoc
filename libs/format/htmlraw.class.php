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

namespace octdoc\format {
    /**
     * Raw html formatter.
     *
     * @octdoc      c:format/htmlraw
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class htmlraw extends \octdoc\format
    /**/
    {
        /**
         * Reference cataloge.
         *
         * @octdoc  p:htmlraw/$references
         * @var     array
         */
        protected $references = array();
        /**/

        /**
         * References within a page.
         *
         * @octdoc  m:htmlraw/$page_references
         * @var     array
         */
        protected $page_references = array();
        /**/

        /**
         * Target for index links.
         *
         * @octdoc  m:htmlraw/$index_target
         * @var     string
         */
        protected $index_target = '';
        /**/

        /**
         * Output handler.
         *
         * @octdoc  p:htmlraw/$output
         * @var     \octdoc\output
         */
        protected $output;
        /**/

        /**
         * Instance of text processor.
         *
         * @octdoc  p:htmlraw/$textproc
         * @var     \octdoc\textproc
         */
        protected $textproc;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:htmlraw/__construct
         * @param   \octdoc\output          $output                 Output handler to use.
         */
        public function __construct(\octdoc\output $output)
        /**/
        {
            parent::__construct();

            $this->output   = $output;
            $this->textproc = new \octdoc\textproc();
            $this->textproc->setEventHandler(function($evt, $text) {
                switch ($evt) {
                case 'p-start':
                    return '<p>';
                case 'p-end':
                    return '</p>';
                case 'mailto':
                    return sprintf('&lt;<a href="mailto:%s">%s</a>&gt;', $text, $text);
                case 'uri':
                    return sprintf('<a target="_blank" href="%s">%s</a>', $text);
                default:
                    return $text;
                }
            });

            // enable markdown post-processor in cases where a supported markdown extension/tool is installed
            if (extension_loaded('discount')) {
                $this->textproc->setPreProcessor(function($text) {
                    $md = \MarkdownDocument::createFromString($text);
                    $md->compile();

                    return $md->getHtml();
                });
            }
        }

        /**
         * Return the formats that are supported by the implemnenting class.
         *
         * @octdoc  m:htmlraw/getFormats
         * @return  array                           Array with strings of the formats.
         */
        public static function getFormats()
        /**/
        {
            return array('htmlraw');
        }

        /**
         * Write header for index page.
         *
         * @octdoc  m:htmlraw/indexHeader
         * @param   resource                        $fh             File handle to write header to.
         * @param   string                          $title          Page title to write.
         */
        protected function indexHeader($fh, $title)
        /**/
        {
            $this->pageHeader($fh, $title);
        }

        /**
         * Write header for documentation page.
         *
         * @octdoc  m:htmlraw/pageHeader
         * @param   resource                        $fh             File handle to write header to.
         * @param   string                          $title          Page title to write.
         */
        protected function pageHeader($fh, $title)
        /**/
        {
            fputs($fh, "<html>\n");
            fputs($fh, "<head>\n");
            fputs($fh, sprintf("<title>%s</title>\n", $title));
            fputs($fh, "</head>\n");
            fputs($fh, "<body>\n");
        }

        /**
         * Write footer for index page.
         *
         * @octdoc  m:htmlraw/indexFooter
         * @param   resource                        $fh             File handle to write header to.
         */
        protected function indexFooter($fh)
        /**/
        {
            $this->pageFooter($fh);
        }

        /**
         * Write footer for documentation page.
         *
         * @octdoc  m:htmlraw/pageFooter
         * @param   resource                        $fh             File handle to write header to.
         */
        protected function pageFooter($fh)
        /**/
        {
            fputs($fh, "</body>\n");
            fputs($fh, "</html>\n");
        }

        /**
         * Write documentation index to temporary directory.
         *
         * @octdoc  m:htmlraw/index
         * @param   string                          $file           File to write index into.
         * @param   array                           $doc            Generic module documentation.
         * @param   array                           $source         Documentation parts extracted from source code.
         */
        public function index($file, array $doc, array $source)
        /**/
        {
            $file = $file . '.html';

            if (!($fp = fopen('php://memory', 'w'))) {
                \octdoc\stdlib::log("unable to open file '$file' for writing");
                return false;
            }

            $this->indexHeader($fp, sprintf('%s -- index', $this->title));

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

            /* write list to file and collect references */
            $refs = array();

            $putList = function(array $tree, $prefix = '') use ($fp, &$refs, &$putList) {
                fputs($fp, "<ul>\n");

                $li = true;
                foreach ($tree as $key => $node) {
                    if (is_int($key)) {
                        array_walk($node['refs'], function($v) use (&$refs, $prefix, $node) {
                            $path = ltrim($prefix . '/' . $v, '/');

                            $refs[$path] = array(
                                'file'   => 'content/' . $node['file'] . '.html',
                                'path'   => $path,
                                'name'   => $v,
                                'page'   => ($v == $node['name']),
                                'anchor' => preg_replace('/[^a-z0-9]/i', '-', $v)
                            );
                        });

                        fputs($fp, sprintf(
                            '<li><a href="%s" title="%s"%s>%s</a>',
                            'content/' . basename($node['file']) . '.html',
                            htmlentities($node['name']),
                            ($this->index_target != ''
                                ? ' target="' . $this->index_target . '"'
                                : ''),
                            htmlentities(explode('/', $node['name'])[1])
                        ));

                        if (!is_string(key($tree))) fputs($fp, '</li>');

                        $li = false;
                    } elseif (count($node) > 0) {
                        if ($li) {
                            fputs($fp, '<li>' . htmlentities($key));
                        }

                        $putList($node, $prefix . '/' . $key);

                        fputs($fp, "</li>\n");

                        $li = true;
                    }
                }

                fputs($fp, "</ul>\n");
            };

            foreach ($source as $section => $part) {
                // section header
                $section = (isset(\octdoc\def::$sections[$section])
                            ? \octdoc\def::$sections[$section]
                            : $section = ucfirst($section));

                fputs($fp, sprintf("<h2>%s</h2>\n", $section));

                foreach ($part as $type => $files) {
                    if (count($files) == 0) continue;

                    // type header
                    fputs($fp, sprintf("<h3>%s</h3>\n", \octdoc\def::$types[$type]));

                    if (count(($tree = $getTree($files))) > 0) {
                        $putList($tree);
                    }
                }
            }

            $this->references = array_merge($this->references, $refs);

            $this->indexFooter($fp);

            rewind($fp);

            $this->output->addFile($file, stream_get_contents($fp));

            fclose($fp);
        }

        /**
         * Write documentation for a specified file.
         *
         * @octdoc  m:htmlraw/page
         * @param   string                          $file           File to write documentation into.
         * @param   string                          $title          Page title.
         * @param   array                           $doc            Documentation to write.
         */
        public function page($file, $title, array $doc)
        /**/
        {
            $file = 'content/' . $file . '.html';

            if (!($fp = fopen('php://memory', 'w'))) {
                \octdoc\stdlib::log("unable to open file '$file' for writing");
                return false;
            }

            $this->page_references = array();

            $this->pageHeader($fp, sprintf('%s -- %s', $this->title, $title));

            $type  = '';
            $depth = \octdoc\def::$depth['scope'];

            foreach ($doc as $part) {
                // write a section header
                if ($type != $part['type']) {
                    $type = $part['type'];

                    fputs($fp, sprintf("<h%1\$d>%2\$s</h%1\$d>\n", \octdoc\def::$depth[$type], htmlentities(\octdoc\def::$types[$type])));

                    $depth = \octdoc\def::$depth[$type] + 1;
                }

                if (($pos = strpos($part['scope'], '/')) !== false) {
                    $anchor = preg_replace('/[^a-z0-9]/i', '-', $part['scope']);

                    fputs($fp, sprintf(
                        "<a name=\"%1\$s\"></a><h%2\$d>%3\$s</h%2\$d>\n",
                        $anchor,
                        $depth,
                        substr($part['scope'], $pos + 1)
                    ));

                    $this->page_references[$anchor] = substr($part['scope'], $pos + 1);
                }

                // write description
                if (trim($part['text']) != '') {
                    fputs($fp, $this->textproc->process($part['text']));
                }

                // write included source code
                if (trim($tmp = $part['source']) != '') {
                    // cut preceeding spaces but keep indentation
                    if (preg_match('/^( +)/', $tmp, $match)) {
                        $tmp = preg_replace('/^' . $match[1] . '/m', '', $tmp);
                    }

                    // remove trailing spaces and cut off last newline
                    $tmp = preg_replace('/[ ]+$/m', '', rtrim($tmp));

                    // remove source lines, if there are too many
                    if (substr_count($tmp, "\n") > \octdoc\def::$source_lines) {
                        $tmp = preg_split("/\n/", $tmp, \octdoc\def::$source_lines + 1);
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

                foreach (\octdoc\def::$sort['attributes'] as $name) {
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
                                $r['name'], $r['type'], $this->textproc->process($r['text'])
                            );
                        }

                        $dd .= "</tbody></table>\n";
                        break;
                    case 'return':
                    case 'throws':
                        $dd .= "<table width=\"100%\"><thead><tr>\n";
                        $dd .= "<th>Type</th><th>Description</th>\n";
                        $dd .= "</tr></thead><tbody>\n";

                        foreach ($attr as $r) {
                            $dd .= sprintf(
                                "<tr><td>%s</td><td>%s</td></tr>\n",
                                $r['type'], $this->textproc->process($r['text'])
                            );
                        }

                        $dd .= "</tbody></table>\n";
                        break;
                    default:
                        if (is_array($attr)) {
                            foreach ($attr as $r) {
                                $dd .= $this->textproc->process($r['text']) . '<br />';
                            }
                        } else {
                            $dd = $this->textproc->process($attr);
                        }
                        break;
                    }

                    if ($dd) {
                        fputs($fp, sprintf("<dt>%s</dt>\n", htmlentities($name)));
                        fputs($fp, sprintf("<dd>%s</dd>\n", $dd));
                    }
                }

                fputs($fp, "</dl>\n");
            }

            $this->pageFooter($fp);

            rewind($fp);

            $this->output->addFile($file, stream_get_contents($fp));

            fclose($fp);

            return true;
        }
    }
}