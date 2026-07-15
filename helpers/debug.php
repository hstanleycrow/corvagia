<?php

declare(strict_types=1);

/**
 * Debug helpers. Not for production use.
 */

if (!function_exists('pd')) {
    // print and die
    function pd(?string $text): void
    {
        die($text);
    }
}

if (!function_exists('vdd')) {
    // var_dump and die
    function vdd(mixed $var): void
    {
        var_dump($var);
        die();
    }
}

if (!function_exists('prd')) {
    // print_r and die
    function prd(mixed $var): void
    {
        echo '<pre>';
        print_r($var);
        die();
    }
}
