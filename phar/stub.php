#!/usr/bin/env php
<?php

/**
 * octdoc PHAR stub.
 *
 * @octdoc      h:phar/stub
 * @copyright   copyright (c) 2012 by Harald Lapp
 * @author      Harald Lapp <harald@octris.org>
 */
/**/

if (!class_exists('PHAR')) {
    print 'unable to execute -- wrong PHP version\n';
    exit(1);
}

Phar::mapPhar();
include 'phar://octdoc.phar/main.class.php';

$main = new \octdoc\main();
$main->run();

__HALT_COMPILER();