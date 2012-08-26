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
     * HTML output with basic user interface.
     *
     * @octdoc      c:output/htmlui
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class htmlui extends \octdoc\format\htmlraw
    /**/
    {
        /**
         * Write header for index page.
         *
         * @octdoc  m:htmlui/indexHeader
         * @param   resource                        $fh             File handle to write header to.
         * @param   string                          $title          Page title to write.
         */
        protected function indexHeader($fh, $title)
        /**/
        {
            fputs($fh, <<<HTML
<html>
    <head>
        <title>${title}</title>
        <style type="text/css">
        body {
            font-family: Verdana, Arial, Helvetica, sans-serif;
            font-size:   0.9em;
            margin:  0;
            padding: 0;
        }
        #form {
            position:      fixed;
            border-bottom: 1px solid #777;
            background-color: #fff;
            top:           0;
            left:          0;
            right:         0;
            height:        24px;
        }
        #form form {
            margin:  3px;
            padding: 0;
        }
        #form form input {
            display:               block;
            width:                 100%;
            border:                1px solid #999;
            border-radius:         5px;
            -moz-border-radius:    5px;
            -webkit-border-radius: 5px;
            outline:               none;
            line-height:           1.5em;
        }
        #toc {
            margin-top: 24px;
            left:       0;
            right:      0;
            display:    block;
        }
        #refs {
            margin-top: 24px;
            left:       0;
            right:      0;
            display:    none;
        }
        #refs ul {
            margin:  0;
            padding: 0;
        }
        #refs li {
            margin:           0;
            padding:          5px 10px;
            display:          block;
            border-bottom:    1px solid #aaa;
            background-color: #e6ffcc;
            cursor:           pointer;
        }
        #refs li:hover {
            background-color: #ddd;
        }
        #refs li small {
            font-size:      0.7em;
            color:          #777;
        }
        </style>
        <script type="text/javascript">
        var octdoc = (function() {
            var refs = {};

            var e_refs;
            var e_toc;

            function update() {
                var phrase = document.getElementById('phrase');
                var val    = phrase.value;
                var show   = {};

                if (val == '') {
                    e_refs.style.display = 'none';
                    e_toc.style.display = 'block';
                } else {
                    e_refs.style.display = 'block';
                    e_toc.style.display = 'none';
                }

                for (var name in refs) {
                    if (name.indexOf(val) >= 0) {
                        show[refs[name]] = true;
                    }
                }

                var li = e_refs.getElementsByTagName('li');

                for (var i = 0, cnt = li.length; i < cnt; ++i) {
                    li[i].style.display = (li[i].id in show
                                        ? 'block'
                                        : 'none');
                }
            }

            window.onload = function() {
                e_refs = document.getElementById('refs');
                e_toc  = document.getElementById('toc');

                var cb = function() {
                    window.setTimeout(function() {
                        update();
                    }, 50);
                }

                phrase.onkeydown = cb;
                phrase.oninput   = cb;
                phrase.onpaste   = cb;
            }

            return {
                'setRefs': function(r) {
                    refs = r;
                }
            };
        })();
        </script>
    </head>

    <body>
        <div id="form">
            <form>
                <input id="phrase" type="text" autocomplete="off" />
            </form>
        </div>
        <div id="toc">
HTML
            );
        }

        /**
         * Write header for documentation page.
         *
         * @octdoc  m:htmlui/pageHeader
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
         * @octdoc  m:htmlui/indexFooter
         * @param   resource                        $fh             File handle to write header to.
         */
        protected function indexFooter($fh)
        /**/
        {
            $data = array();

            fputs($fh, '</div><div id="refs"><ul>');

            $i = 0;
            foreach ($this->references as $ref => $meta) {
                $id = 'ref' . (++$i);

                $data[$ref] = $id;

                fputs($fh, sprintf(
                    '<li id="%s" onclick="parent.content.location.href=\'%s\'">%s<br /><small>%s</small></li>',
                    $id,
                    $meta['file'],
                    $meta['name'],
                    $meta['path']
                ));
            }

            fputs($fh, sprintf(<<<HTML
            </ul>
        </div>
        <script type="text/javascript">
        octdoc.setRefs(%s);
        </script>
    </body>
</html>
HTML
            , json_encode($data)));
        }

        /**
         * Write footer for documentation page.
         *
         * @octdoc  m:htmlui/pageFooter
         * @param   resource                        $fh             File handle to write header to.
         */
        protected function pageFooter($fh)
        /**/
        {
            fputs($fh, "</body>\n");
            fputs($fh, "</html>\n");
        }

        /**
         * Preprocess. This method get's called after all pages and the index page have been written.
         *
         * @octdoc  m:htmlui/preprocess
         */
        public function postprocess()
        /**/
        {
            $this->output->addFile('doc/index.html', <<<HTML
<html>
    <frameset cols="200,*">
        <frame name="toc" src="toc.html" />
        <frame name="content" src="" />
    </frameset>
</html>
HTML
            );
        }

        /**
         * Write documentation index to temporary directory.
         *
         * @octdoc  m:htmlui/index
         * @param   string                          $file           File to write index into.
         * @param   array                           $doc            Generic module documentation.
         * @param   array                           $source         Documentation parts extracted from source code.
         */
        public function index($file, array $doc, array $source)
        /**/
        {
            parent::index('toc', $doc, $source);
        }
    }
}
