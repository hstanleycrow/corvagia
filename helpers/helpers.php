<?php

declare(strict_types=1);

function redirect(string $to): void
{
    header("Location: $to");
    exit;
}

function isLogged(): bool
{
    return isset($_SESSION['isLogged']) && $_SESSION['isLogged'] === true;
}

function between(int|float $value, int|float $min, int|float $max): bool
{
    return $value >= $min && $value <= $max;
}

function slug(string $string): string
{
    $string = trim($string);

    $string = str_replace(
        array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä', '&aacute;', '&Aacute;'),
        array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'a', 'A'),
        $string
    );

    $string = str_replace(
        array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë', '&eacute;', '&Eacute;'),
        array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'e', 'E'),
        $string
    );

    $string = str_replace(
        array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î', '&iacute;', '&Iacute;'),
        array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'i', 'I'),
        $string
    );

    $string = str_replace(
        array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô', '&oacute;', '&Oacute;'),
        array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'o', 'O'),
        $string
    );

    $string = str_replace(
        array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü', '&uacute;', '&Uacute;'),
        array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'u', 'U'),
        $string
    );

    $string = str_replace(
        array('ñ', 'Ñ', 'ç', 'Ç', '&ntilde;', '&Ntilde;'),
        array('n', 'N', 'c', 'C', 'n', 'N'),
        $string
    );

    $string = str_replace(
        array(
            "\\", "¨", "º", "-", "~",
            "#", "@", "|", "!", "\"",
            "$", "%", "&", "/",
            "(", ")", "?", "'", "¡",
            "¿", "[", "^", "`", "]",
            "+", "}", "{", "¨", "´",
            ">", "<", ";", ",", ":",
        ),
        '',
        $string
    );
    $string = str_replace(" ", "-", $string);

    return strtolower($string);
}

/**
 * Limpia un slug escrito por el usuario preservando los guiones medios.
 * Convierte acentos, pasa a minúsculas y elimina caracteres inválidos.
 */
function sanitize_slug(string $value): string
{
    $value = trim($value);
    $value = mb_strtolower($value, 'UTF-8');

    $from = ['á','à','ä','â','Á','À','Â','Ä','é','è','ë','ê','É','È','Ê','Ë',
             'í','ì','ï','î','Í','Ì','Ï','Î','ó','ò','ö','ô','Ó','Ò','Ö','Ô',
             'ú','ù','ü','û','Ú','Ù','Û','Ü','ñ','Ñ','ç','Ç'];
    $to   = ['a','a','a','a','a','a','a','a','e','e','e','e','e','e','e','e',
             'i','i','i','i','i','i','i','i','o','o','o','o','o','o','o','o',
             'u','u','u','u','u','u','u','u','n','n','c','c'];
    $value = str_replace($from, $to, $value);

    $value = preg_replace('/\s+/', '-', $value);
    $value = preg_replace('/[^a-z0-9-]/', '', $value);
    $value = preg_replace('/-+/', '-', $value);
    return trim($value, '-');
}

/**
 * Retorna la ruta WebP equivalente a la imagen original.
 * Ej: 'uploads/x.jpg' → 'uploads/x.webp'
 */
function webp_src(string $path): string
{
    return preg_replace('/\.[^.]+$/', '.webp', $path);
}
