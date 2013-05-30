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
         * Target for index links.
         *
         * @octdoc  m:htmlui/$index_target
         * @var     string
         */
        protected $index_target = 'content';
        /**/

        /**
         * Title of current page.
         *
         * @octdoc  m:htmlui/$page_title
         * @var     string
         */
        protected $page_title = '';
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:htmlui/__construct
         * @param   \octdoc\output          $output                 Output handler to use.
         */
        public function __construct(\octdoc\output $output)
        /**/
        {
            parent::__construct($output);
        }

        /**
         * Return the formats that are supported by the implemnenting class.
         *
         * @octdoc  m:htmlui/getFormats
         * @return  array                           Array with strings of the formats.
         */
        public static function getFormats()
        /**/
        {
            return array('htmlui');
        }

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
            background-color: #eee;
        }
        a {
            color: #aa0000;
        }
        #form {
            position:      fixed;
            border-bottom: 1px solid #777;
            background-color: #eee;
            top:           0;
            left:          0;
            right:         0;
            height:        24px;
        }
        #form form {
            margin:   0 3px;
            padding:  0;
        }
        #form a.clr {
            position:        absolute;
            top:             0;
            right:           10px;
            line-height:     24px;
            text-decoration: none;
            color:           #aaa;
        }
        #form a.clr:hover {
            color:           #aa0000;
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
            margin:                2px 0;
        }
        #toc {
            left:       0;
            right:      0;
            display:    block;
            padding:    10px;
        }
        #toc ul {
            padding-left: 0;
            margin-left:  1.2em;
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
        #refs li.page {
            background-color: #cce6ff;
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
            var e_form;

            var e_clr = null;

            function clr_show() {
                if (e_clr == null) {
                    e_clr = document.createElement('a');
                    e_clr.className = 'clr'; 
                    e_clr.innerHTML = '&times;';
                    e_clr.href      = 'javascript://';

                    e_clr.onclick   = function() {
                        var phrase = document.getElementById('phrase');
                        phrase.value = '';

                        window.setTimeout(function() {
                            update();
                        }, 50);
                    };

                    e_form.appendChild(e_clr);
                }

                e_clr.style.visibility = 'visible';
            }

            function clr_hide() {
                if (e_clr == null) return;

                e_clr.style.visibility = 'hidden';
            }

            function update() {
                var phrase = document.getElementById('phrase');
                var val    = phrase.value.toLowerCase();
                var show   = {};

                if (val == '') {
                    e_refs.style.display = 'none';
                    e_toc.style.display = 'block';

                    clr_hide();
                } else {
                    e_refs.style.display = 'block';
                    e_toc.style.display = 'none';

                    clr_show();
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
                e_form = document.getElementById('form');

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
                <input id="phrase" type="text" autocomplete="off" value="" />
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
            fputs($fh, <<<HTML
<html>
    <head>
        <title>${title}</title>
        <style type="text/css">
        body {
            font-family:      Verdana, Arial, Helvetica, sans-serif;
            font-size:        0.9em;
            margin:           0;
            padding:          0;
            background-color: #eee;
        }
        pre {
            position:         relative;
            border:           1px solid #ccc;
            background-color: #ffeecc;
            padding:          5px;
        }
        pre.shrinked_src:before, pre.expanded_src:before {
            position:    absolute;
            font-size:   2em;
            left:        0;
            bottom:      0;
            width:       100%;
            text-align:  center;

            color:       #aaa;

            font-family:     Verdana, Arial, Helvetica, sans-serif;
            font-size:       0.8em;
        }
        pre.shrinked_src:before {
            content:     "\\25BC  expand \\25BC";
        }
        pre.expanded_src:before {
            content:     "\\25B2  shrink \\25B2";
        }
        a {
            color:            #aa0000;
        }
        table {
            font-family:     Verdana, Arial, Helvetica, sans-serif;
            font-size:       0.9em;
            border:          1px solid #ccc;
            border-collapse: collapse;
        }
        table th {
            text-align:       left;
            background-color: #ddd;
        }
        table td {
            padding:        2px;
            vertical-align: top;
        }
        table tbody tr td {
            border-top:     1px solid #ddd;
        }
        #content {
            margin-right:     250px;
            border-right:     1px solid #ccc;
            background-color: #fff;
            min-height:       100%;
            padding:          10px;
        }
        #content dt {
            margin:         10px 0 5px 0;
            border-bottom:  1px dotted #777;
        }
        #sidebar {
            position: fixed;
            top:      10px;
            right:    10px;
            width:    230px;
        }
        #sidebar ul {
            list-style:   none;
            margin-left:  0;
            padding-left: 1em;
            text-indent:  -1em;
        }
        #sidebar div.button {
            background-color: #aaa;
            color:            #fff;

            border-radius:    5px;
            margin-right:     10px;
            padding:          2px;

            display:          inline-block;
            width:            80px;
            text-align:       center;

            cursor:           pointer;
        }
        #sidebar div.button:hover {
            background-color: #aa0000;
        }
        #sidebar div.button a {
            font-size:        0.9em;
            font-weight:      bold;
            text-decoration:  none;
            color:            #fff;
        }

        @media print {
            pre.shrinked_src:before {
                content: "";
            }
            pre.expanded_src:before {
                content: "";
            }
            #sidebar {
                display: none;
            }
            #content {
                margin-right: 0;
                border-right: 0;
            }
        }
        </style>
        <script type="text/javascript">
        var octdoc = (function() {
            var o = {};

            o.toggleSource = function(id) {
                var s = document.getElementById('shrinked_src_' + id);
                var e = document.getElementById('expanded_src_' + id);

                if (s.style.display == 'block') {
                    s.style.display = 'none';
                    e.style.display = 'block';
                } else {
                    s.style.display = 'block';
                    e.style.display = 'none';
                }
            }

            return o;
        })();
        </script>
    </head>
    <body>
        <div id="content">
HTML
            );
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

                $data[strtolower($ref)] = $id;

                fputs($fh, sprintf(
                    '<li id="%s" onclick="parent.content.location.href=\'%s\'"%s>%s<br /><small>%s</small></li>',
                    $id,
                    $meta['file'] . '#' . $meta['anchor'],
                    ($meta['page'] ? ' class="page"' : ''),
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
            fputs($fh, <<<HTML
        </div>
        <div id="sidebar">
            <h1>$this->page_title</h1>

            <div class="button"><a href="javascript://" onclick="document.body.scrollTop = document.documentElement.scrollTop = 0;">top</a></div><div class="button"><a href="javascript://" onclick="window.print();">print</a></div>

            <ul>
HTML
            );

            array_shift($this->page_references);

            foreach ($this->page_references as $ref => $name) {
                fputs($fh, sprintf(
                    "<li>&#187; <a href=\"#%s\">%s</a></li>\n",
                    $ref,
                    $name
                ));
            }

            fputs($fh, <<<HTML
            </ul>
        </div>
    </body>
</html>
HTML
            );
        }

        /**
         * Preprocess. This method get's called after all pages and the index page have been written.
         *
         * @octdoc  m:htmlui/preprocess
         */
        public function postprocess()
        /**/
        {
            $this->output->addFile('index.html', <<<HTML
<html>
    <head>
        <title>$this->title</title>
    </head>
    <frameset cols="250,*">
        <frame name="toc" src="toc.html" />
        <frame name="content" src="title.html" />
    </frameset>
</html>
HTML
            );

            $this->output->addFile('title.html', sprintf(<<<HTML
<html>
    <head>
        <style type="text/css">
        body {
            font-family:      Verdana, Arial, Helvetica, sans-serif;
            font-size:        0.9em;
            margin:           0;
            padding:          0;
        }
        hr {
            border-top:     1px #000 dotted;
            border-left:    0;
            border-bottom:  0;
            border-right:   0;
            margin:         5px;
        }
        #content {
            text-align:     center;
            padding:        10px;
        }
        </style>
    </head>
    <body>
        <div id="content">
            <h1>%s</h1>
            %s
            <hr />            
            <p>Last updated: %s</p>
        </div>
    </body>
</html>
HTML
                , 
                $this->getProperty('title'),
                $this->getProperty('author', '<p>copyright &copy; 2013 by %s</p>'),
                strftime('%Y-%m-%d %H:%M:%S')
            ));

            $this->output->addFile('meta.json', json_encode(array(
                'title' => $this->title
            )));
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

        /**
         * Write source code block.
         *
         * @octdoc  m:htmlui/source
         * @param   resource                        $fp             File to write source code block to.
         * @param   string                          $source         Source code block to write.
         * @param   string                          $tpl            Template for writing source block.
         * @return  bool                                            Returns true if source code was shrinked.
         */
        protected function source($fp, $source, $tpl)
        /**/
        {
            $uniq_id  = uniqid('', true);
            $shrinked = parent::source($fp, $source, sprintf('<pre id="shrinked_src_%s" style="display: block;">%%s</pre>', $uniq_id));

            if ($shrinked) {
                fputs($fp, <<<HTML
            <pre id="expanded_src_$uniq_id" class="expanded_src" style="cursor: pointer; display: none;" onclick="octdoc.toggleSource('$uniq_id')">$source</pre>
            <script type="text/javascript">
            (function() {
                var e = document.getElementById('shrinked_src_$uniq_id');

                e.className    = 'shrinked_src';
                e.style.cursor = 'pointer';
                e.onclick = function() {
                    octdoc.toggleSource('$uniq_id');
                }
            })();
            </script>
HTML
                );
            }
        }

        /**
         * Write documentation for a specified file.
         *
         * @octdoc  m:htmlui/page
         * @param   string                          $file           File to write documentation into.
         * @param   string                          $title          Page title.
         * @param   array                           $doc            Documentation to write.
         */
        public function page($file, $title, array $doc)
        /**/
        {
            $this->page_title = $title;

            parent::page($file, $title, $doc);
        }
    }
}
