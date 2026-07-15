<?php

declare(strict_types=1);

if (!isset($debug)) {
    $debug = false;
}

if ($debug) {
    error_reporting(E_ALL ^ E_WARNING);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
