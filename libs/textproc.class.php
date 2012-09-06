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
     * Event based text processer. This class purpose is to parse inline tags and other parsable stuff
     * and call an event handler for further processing.
     *
     * @octdoc      c:octdoc/textproc
     * @copyright   copyright (c) 2012 by Harald Lapp
     * @author      Harald Lapp <harald@octris.org>
     */
    class textproc
    /**/
    {
        /**
         * PreProcessor callback.
         *
         * @octdoc  p:octdoc/$pre_processor
         * @var     callable
         */
        protected $pre_processor;
        /**/

        /**
         * PostProcessor callback.
         *
         * @octdoc  p:octdoc/$post_processor
         * @var     callable
         */
        protected $post_processor;
        /**/

        /**
         * Event handler.
         *
         * @octdoc  p:octdoc/$event_handler
         * @var     callable
         */
        protected $event_handler;
        /**/

        /**
         * Constructor.
         *
         * @octdoc  m:octdoc/__construct
         */
        public function __construct()
        /**/
        {
            $this->event_handler = function($event, $text) {
                return $text;
            };
            $this->pre_processor = $this->post_processor = function($text) {
                return $text;
            };
        }

        /**
         * Set a post processing callback.
         *
         * @octdoc  m:octdoc/setPostProcessor
         * @param   callable            $cb                     PostProcessing callback.
         */
        public function setPostProcessor(callable $cb)
        /**/
        {
            $this->post_processor = $cb;
        }

        /**
         * Set a pre processing callback.
         *
         * @octdoc  m:octdoc/setPreProcessor
         * @param   callable            $cb                     PreProcessor callback.
         */
        public function setPreProcessor(callable $cb)
        /**/
        {
            $this->pre_processor = $cb;
        }

        /**
         * Add event handler for parsed content.
         *
         * @octdoc  m:octdoc/setEventHandler
         * @param   callable            $cb                     Callback to call.
         */
        public function setEventHandler(callable $cb)
        /**/
        {
            $this->event_handler = $cb;
        }

        /**
         * Process a text snippet
         *
         * @octdoc  m:octdoc/process
         * @param   string              $text                   Text to process.
         */
        public function process($text)
        /**/
        {
            $event_handler  = $this->event_handler;
            $pre_processor  = $this->pre_processor;
            $post_processor = $this->post_processor;

            // fix whitespace characters in text and call pre-processor
            $text = strtr(str_replace("\r\n", "\n", $text), "\t\r", " \n");
            $text = preg_replace('/ {2,}/', ' ', $text);
            $text = trim(preg_replace('/\n{3,}/', "\n\n", $text), "\n");
            $text = preg_replace('/ +$/m', '', trim($text, "\n"));

            $text = $pre_processor($text);

            // prepare inline tags
            $tags = array();
            $text = preg_replace_callback('/\{@[a-zA-Z_0-9]+ [^\}]+\}/', function($m) use (&$tags) {
                $tags[$key = md5($m[0])] = $m[0];

                return '{@' . $key . '}';
            }, $text);

            // separate text in blocks and words and process them.
            $blocks = explode("\n\n", $text);
            $result = array();

            foreach ($blocks as $block) {
                $result[] = $event_handler('p-start', '');

                $words = explode(' ', $block);

                foreach ($words as &$word) {
                    if (preg_match('/\{@([a-f0-9]{32})\}/', $word, $m)) {
                        // inline tag
                        $word = $event_handler('tag', (isset($tags[$m[0]]) ? $tags[$m[0]] : ''));
                    } elseif (preg_match('/^<([^\s<>]+@[^\s<>]+)>$/', $word, $m)) {
                        // e-mail address
                        $word = $event_handler('mailto', $m[1]);
                    } elseif (preg_match('/^(https?|s?ftp):\/\/[\/]+/', $word)) {
                        // some uri
                        $word = $event_handler('uri', $word);
                    }
                }

                $result[] = implode(' ', $words);

                $result[] = $event_handler('p-end', "\n\n");
            }

            return $post_processor(implode('', $result));
        }
    }
}
