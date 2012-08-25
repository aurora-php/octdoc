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
     * Simple text formatter.
     *
     * @octdoc      c:octdoc/format
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class format
    /**/
    {
        /**
         * Tab width.
         *
         * @octdoc  p:format/$tab_width
         * @var     int
         */
        protected $tab_width = 4;
        /**/

        /**
         * Headlines.
         *
         * @octdoc  p:format/$headlines
         * @var     array
         */
        protected static $headlines = array(
            '=' => 1,
            '-' => 2,
        );
        /**/

        /**
         * Inline parser pattern, to be filled by constructor.
         *
         * @octdoc  p:format/$inline
         * @var     string
         */
        protected static $inline = null;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:format/__construct
         */
        public function __construct()
        /**/
        {
            if (is_null(self::$inline)) {
                self::$inline = '/' .
                    '(?<=[ ]|\A)' .                                                         # either space or beginning of subject required in front of inline formatting
                    '(' .
                        '(\*|\~|_|@|-)([^\s]|[^\s].*?)\2(?!\2)(?=\W|\Z)' .                  # inline formatting
                    '|' .
                        '((https?)\:\/\/[^\s]+|s?ftp:\/\/|ftps:\/\/|file:\/\/|mailto:)' .   # simple URLs: http, https, ftp, sftp, ftps, mailto
                    '|' .
                        '(&(?:m|n)dash;)' .                                                 # dashes
                    ')/si';
            }
        }

        /**
         * Set tab width of text to parse.
         *
         * @octdoc  m:format/setTabWidth
         * @param   int                 $width                  Tab width.
         */
        public function setTabWidth($width)
        /**/
        {
            $this->tab_width = $width;
        }

        /**
         * Process text.
         *
         * @octdoc  m:format/process
         * @param   string              $text                   Text to process.
         * @return  string                                      HTML snippet.
         */
        public function process($inp)
        /**/
        {
            $dom = new \DOMDocument();
            $doc = $dom->appendChild($dom->createElement('html'));

            $fh = fopen('php://memory', 'w+');
            fputs($fh, $inp);

            rewind($fh);

            // $this->calcTabWidth($fh);
            //
            // rewind($fh);

            $this->parseBlock($doc, $fh);

            fclose($fh);

            return $dom->saveXML();
        }

        /**
         * Get next line of input stream.
         *
         * @octdoc  m:format/getNextLine
         * @param   resource                $fh             File handle to read from.
         * @return  string                                  Read line.
         */
        protected function getNextLine($fh)
        /**/
        {
            if (!feof($fh)) {
                $return = $this->deTab(rtrim(fgets($fh, 4096)));
            } else {
                $return = false;
            }

            return $return;
        }

        /**
         * Determine indent of line.
         *
         * @octdoc  m:format/getIndent
         * @param   string                  $line           Text line.
         * @return  int                                     Indent of line.
         */
        protected function getIndent($line)
        /**/
        {
            preg_match('/^( *)/', $line, $match);
            $line_indent = strlen($match[1]);

            return (int)($line_indent / $this->tab_width);
        }

        /**
         * Block parser.
         *
         * @octdoc  m:format/parseBlock
         * @param   \DOMNode                $parent         Parent DOM node to add parsed content to.
         * @param   resource                $fh             File handle to read from.
         */
        protected function parseBlock($parent, $fh)
        /**/
        {
            $indent = 0;
            $line   = '';
            $buffer = array();
            $state  = 0;
            $eat    = false;
            $qlevel = 0;        // quotation level
            $read   = false;    // read one more line
            $dom    = $parent->ownerDocument;

            $quotes = array($parent);

            $buffer_cnt = function() use (&$buffer) {
                return count($buffer);
            };
            $buffer_len = function() use (&$buffer) {
                return strlen(implode(' ', $buffer));
            };
            $buffer_eat = function($chr = ' ') use (&$buffer) {
                $return = implode($chr, $buffer);
                $buffer = array();

                return $return;
            };

            while (true) {
                if ($line == '' && ($read = true) && ($line = $this->getNextLine($fh)) === false) {
                    // no more lines to parse, but if there's still something in
                    // the buffer, one more iteration is required
                    if ($buffer_cnt() == 0) {
                        break;
                    }
                }

                // determine quotation level
                if ($read && (preg_match('/^(> *)+/', $line, $qmatch) || ($qlevel > 0 && trim($line) != ''))) {
                    if (count($qmatch) == 2) {
                        $tmp_level = substr_count($qmatch[0], '>');
                        $tmp_match = rtrim($qmatch[0]);
                        $tmp_spc   = strlen($tmp_match) % 4;

                        $line = preg_replace('/^' . $tmp_match . ' {0,' . ($tmp_spc == 0 ? 0 : 4 - $tmp_spc) . '}/', '', $line);
                    } else {
                        $tmp_level = 0;
                    }

                    if ($qlevel != $tmp_level) {
                        if ($buffer_cnt() > 0) {
                            // buffer is not empty -> exec inline parser
                            $p = $dom->createElement('p');
                            $parent->appendChild($p);

                            $this->parseInline($p, $buffer_eat());
                        }

                        if ($qlevel < $tmp_level) {
                            // indent
                            $parent = $quotes[$qlevel];

                            for ($i = $qlevel; $i < $tmp_level; ++$i) {
                                $parent = $parent->appendChild($dom->createElement('blockquote'));
                                array_push($quotes, $parent);
                            }
                        } elseif ($qlevel > $tmp_level) {
                            // outdent
                            $parent = $quotes[$tmp_level];
                        }

                        $indent = 0;
                    }

                    $qlevel = $tmp_level;
                    $read   = false;
                    // continue;
                }

                // determine indent level for line
                $line_indent = $this->getIndent($line);

                // test for empty line -> new paragraph
                if ($eat || trim($line) == '' || $line_indent < $indent) {
                    if ($buffer_cnt() > 0 && $state == 0) {
                        // buffer is not empty for default state -> exec inline parser
                        $p = $dom->createElement('p');
                        $parent->appendChild($p);

                        $this->parseInline($p, $buffer_eat());
                    } elseif ($state == 1) {
                        // plugin state -> add buffer to parent DOMs CDATA
                        $cdata = $buffer_eat("\n");

                        if (is_null($parent->lastChild) || $parent->lastChild->nodeType != XML_CDATA_SECTION_NODE) {
                            $parent->appendChild($dom->createCDATASection($cdata));
                        } else {
                            $parent->lastChild->data .= "\n\n" . $cdata;
                        }
                    }

                    if ($eat) {
                        $eat = false;
                    } else {
                        if (trim($line) != '' && $indent > 0) {
                            // lower indent for line and continue parsing
                            --$indent;
                            $state = 0;

                            do {
                                $parent = $parent->parentNode;
                            } while (in_array($parent->nodeName, array('ul', 'ol')));
                        }

                        continue;
                    }
                }

                // remove indent only if in normal parser state
                if ($state == 0) {
                    $line = trim($line);
                }

                // block rule parser
                if ($state == 0 && preg_match('/^([=-]+)$/', $line, $match) && $buffer_cnt() > 0) {
                    // headline matched
                    $level = self::$headlines[substr($match[0], 0, 1)];
                    $e = $dom->createElement('h' . $level);

                    $this->parseInline($e, $buffer_eat());
                    $parent->appendChild($e);

                    // add entry to index element
                    $this->addIndexItem($e, $level);

                    // eat line
                    $line = '';
                } elseif ($state == 0 && preg_match('/^(\*|\+|-|#) {' . ($this->tab_width - 1) . '}/', $line, $match)) {
                    if ($buffer_cnt() > 0) {
                        $eat = true;
                        continue;
                    }

                    // sorted / unsorted list
                    $type = ($match[1] == '#' ? 'ol' : 'ul');

                    if (is_null($parent->lastChild) || $parent->lastChild->nodeName != $type) {
                        // only create list container, if there is not already one available
                        $e = $dom->createElement($type);
                        $parent->appendChild($e);
                    } else {
                        // previous element is list container of same type
                        $e = $parent->lastChild;
                    }

                    $parent = $e->appendChild($dom->createElement('li'));
                    ++$indent;

                    // expand line to fit new line indent
                    $line = str_repeat(' ', ($indent * $this->tab_width)) .
                            substr($line, strlen($match[0]));
                } elseif ($state == 0 && preg_match('/^---+/', $line, $match) && $buffer_cnt() == 0) {
                    // section
                    $parent->appendChild($dom->createElement('hr'));

                    // eat line
                    $line = '';
                } elseif ($state == 0 && preg_match('/^\.\. {' . ($this->tab_width - 2) . '}(?P<type>[a-z0-3]+): (?P<args>[^, ]*(?:, *[^, ]*|)+)$/', $line, $match)) {
                    // block plugin
                    if ($buffer_cnt() > 0) {
                        // buffer not empty -- process it first
                        $eat = true;
                        continue;
                    }

                    $e = $dom->createElement('plugin');
                    $e->setAttribute('type', $match['type']);

                    array_filter(
                        explode(',', $match['args']),
                        function($v) use (&$e) {
                            static $i = 0;
                            $e->setAttribute('param' . (++$i), trim($v));
                        }
                    );

                    $parent = $parent->appendChild($e);
                    ++$indent;
                    $state = 1;

                    // execute plugin for input processing
                    $class    = 'lima_ltext_plugin_' . $match['type'];
                    $instance = $class::getInstance();

                    $instance->input();

                    // eat line
                    $line = '';
    /*            } elseif ($state == 0 && preg_match('/^\[\d+\]: .+/', $line, $match)) {
                    // footnote
                    throw new Exception('footnote tobe implemented!'); */
                } elseif ($state == 0 && preg_match('/^\+(-+\+)+$/', $line, $match)) {
                    // table
                    $line = $this->parseTable($parent, $fh, $line);
                } else {
                    // no parser rule match -- just add line to textbuffer for later inline parsing
                    $buffer[] = $line;

                    // eat line
                    $line = '';
                }

                $read = false;
            }
        }

        /**
         * ASCII Table parser.
         *
         * @octdoc  m:format/parseTable
         * @param   \DOMNode            $parent             Parent node to add table to.
         * @param   resource            $fh                 File handle to read from.
         * @param   string              $line               Current line to parse.
         */
        protected function parseTable($parent, $fh, $line)
        /**/
        {
            $dom     = $parent->ownerDocument;
            $columns = array();
            $width   = array();
            $rows    = array();

            // calculate columns, collect rows
            do {
                $row = trim($line);

                if (!preg_match('/^(\+(-+\+)+|\+(=+\+)+|\|.*\|)$/', $row)) {
                    // no table separator nor table row
                    break;
                }

                $rows[] = $row;

                if (preg_match_all('/(\+[-=]+(?=\+)|\+$)/', $row, $match, PREG_OFFSET_CAPTURE)) {
                    // row separators
                    array_walk($match[0], function($v) use (&$columns) {
                        $columns[] = $v[1];
                    });
                }
            } while (($line = $this->getNextLine($fh)) !== false);

            $columns = array_unique($columns);
            sort($columns);

            // calculate prozentual width of columns
            $max = 0;

            for ($i = 1, $cnt = count($columns); $i < $cnt; ++$i) {
                $max = max($max, $columns[$i]);
            }

            $max = 100 / $max;

            for ($i = 1, $cnt = count($columns); $i < $cnt; ++$i) {
                $width[] = round($max * ($columns[$i] - $columns[$i - 1]));
            }

            // iterate rows and build internal table representation
            $data = array();
            $cols = count($columns) - 1;
            $r    = -1;

            for ($i = 0, $cnt = count($rows); $i < $cnt; ++$i) {
                $row = $rows[$i];

                if (!preg_match('/[^-=+]/', $row)) {
                    // separator row
                    if (preg_match('/[=]/', $row)) {
                        // mark previous rows as header
                        for ($h = $r; $h >= 0; --$h) {
                            $data[$h]['header'] = true;
                        }
                    }

                    ++$r;
                    continue;
                }

                if (!isset($data[$r])) {
                    $data[$r] = array(
                        'header'  => false,
                        'columns' => array_fill(0, $cols, NULL)
                    );
                }

                $offset = 0;
                $cell   = 0;

                while (preg_match('/(\|[ ](.*?))[ ]\|/', $row, $match, PREG_OFFSET_CAPTURE, $offset)) {
                    $tmp     = array_search($offset, $columns);
                    $offset += strlen($match[0][0]) - 1;

                    if ($tmp === false) {
                        // no column separator but isolated pipe character
                        $data[$r]['columns'][$cell]['content'] .= ' ' . $match[1][0];
                        continue;
                    }

                    $cell = $tmp;

                    if (is_null($data[$r]['columns'][$cell])) {
                        $data[$r]['columns'][$cell] = array(
                            'span'    => 1,
                            'content' => ''
                        );
                    } else {
                        $data[$r]['columns'][$cell]['span']     = 1;
                        $data[$r]['columns'][$cell]['content'] .= "\n";
                    }

                    $data[$r]['columns'][$cell]['content'] .= $match[2][0];
                }

                for ($c1 = 1, $c2 = 0; $c1 < $cols; ++$c1) {
                    if (is_null($data[$r]['columns'][$c1])) {
                        ++$data[$r]['columns'][$c2]['span'];
                    } else {
                        $c2 = $c1;
                    }
                }
            }

            // cleanup
            unset($columns);
            unset($rows);

            // build table DOM
            if (count($data) > 0) {
                $table = $parent->appendChild($dom->createElement('table'));
                $head  = -1;
                $type  = '';

                foreach ($data as $row) {
                    if ((int)$row['header'] != $head) {
                        // switch table part
                        $part = $table->appendChild(
                            $dom->createElement(
                                ($row['header'] ? 'thead' : 'tbody')
                            )
                        );

                        $type = ($row['header'] ? 'th' : 'td');
                        $head = (int)$row['header'];
                    }

                    $col = 0;

                    $tr = $part->appendChild($dom->createElement('tr'));

                    foreach ($row['columns'] as $cell) {
                        if (is_null($cell)) continue;

                        $e = $tr->appendChild(
                            $dom->createElement($type)
                        );

                        if ($cell['span'] > 1) {
                            $e->setAttribute('colspan', $cell['span']);
                        } else {
                            $e->setAttribute('width', $width[$col++] . '%');
                        }

                        if ($inp = fopen('data://text/plain;base64,' . base64_encode($cell['content']), 'r')) {
                            $this->parseBlock($e, $inp);

                            fclose($inp);
                        }
                    }
                }
            }

            // cleanup data table and return
            unset($data);
            unset($width);

            return $line;
        }

        /**
         * Text parser.
         *
         * @octdoc  m:format/processText
         * @param   \DOMNode        $parent                 Parent node to add text to.
         * @param   string          $text                   Text to parse.
         */
        protected function processText($parent, $text)
        /**/
        {
            $dom  = $parent->ownerDocument;
            $text = htmlentities(stripslashes(utf8_decode($text)));

            while ($text != '') {
                if (preg_match('/(&[a-zA-Z0-9]+;)/', $text, $match, PREG_OFFSET_CAPTURE)) {
                    $parent->appendChild($dom->createTextNode(substr($text, 0, $match[0][1])));
                    $text = substr($text, $match[0][1] + strlen($match[0][0]));

                    $parent->appendChild($dom->createEntityReference(substr($match[0][0], 1, -1)));
                } else {
                    $parent->appendChild($dom->createTextNode($text));

                    $text = '';
                }
            }
        }

        /**
         * Inline parser parses references (links) and text formattings.
         *
         * @octdoc  m:format/parseInline
         * @param   \DOMNode                $parent                 Parent node.
         * @param   string                  $line                   Text to parse.
         */
        protected function parseInline($parent, $line)
        /**/
        {
            $dom  = $parent->ownerDocument;
            $line = str_replace('--', '&mdash;', preg_replace('/ -- /', ' &ndash; ', $line));

            while ($line != '') {
                if (preg_match(self::$inline, $line, $match, PREG_OFFSET_CAPTURE)) {
                    // inline formatting
                    if ($match[0][1] > 0) {
                        // text before match
                        $this->processText($parent, substr($line, 0, $match[0][1]));
                    }

                    $line = substr($line, strlen($match[0][0]) + $match[0][1]);

                    if ($match[0][0] == '--') {
                        $parent->appendChild(
                            $dom->createTextNode(
                                '--'
                            )
                        );
                    } elseif (isset($match[2]) && $match[2][1] >= 0 && $match[3][0] != '') {
                        // text formatting
                        switch ($match[2][0]) {
                        case '*':
                            $e = $dom->createElement('b');
                            $parent->appendChild($e);
                            $this->parseInline($e, $match[3][0]);
                            break;
                        case '_':
                            $e = $dom->createElement('u');
                            $parent->appendChild($e);
                            $this->parseInline($e, $match[3][0]);
                            break;
                        case '~':
                            $e = $dom->createElement('i');
                            $parent->appendChild($e);
                            $this->parseInline($e, $match[3][0]);
                            break;
                        case '-':
                            $e = $dom->createElement('del');
                            $parent->appendChild($e);
                            $this->parseInline($e, $match[3][0]);
                            break;
                        case '@':
                            $e = $dom->createElement('code');
                            $parent->appendChild($e);
                            $this->parseInline($e, $match[3][0]);
                            break;
                        }
                    } elseif (isset($match[4]) && $match[4][1] >= 0 && $match[4][0] != '') {
                        // URL
                        $e = $dom->createElement('a');
                        $parent->appendChild($e);
                        $e->setAttribute('href', $match[4][0]);
                        $e->appendChild($dom->createTextNode(utf8_encode($match[4][0])));
                    } elseif (isset($match[6]) && $match[6][1] >= 0 && $match[6][0] != '') {
                        // dashes
                        $parent->appendChild($dom->createElement(preg_replace('/&(.*?);/', '\1', $match[6][0])));
                    }
                } else {
                    // text
                    $this->processText($parent, $line);
                    break;
                }
            }
        }

        /**
         * Convert tab characters to spaces.
         *
         * @octdoc  m:format/deTab
         * @param   string                  $txt                    Text string to detab.
         * @return  string                                          Detabbed text string.
         */
        protected function deTab($txt)
        /**/
        {
            $tab_width = $this->tab_width;

            $txt = preg_replace_callback(
                '/^.*\t.*$/m',
                function($match) use ($tab_width) {
                    $parts = explode("\t", $match[0]);
                    $row   = '';

                    foreach ($parts as $part) {
                        $row .= $part . str_repeat(' ', $tab_width - (strlen($part) % $tab_width));
                    }

                    return rtrim($row);
                },
                $txt
            );

            return $txt;
        }
    }
}
