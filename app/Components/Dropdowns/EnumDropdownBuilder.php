<?php

declare(strict_types=1);

namespace App\Components\Dropdowns;

class EnumDropdownBuilder
{
    public static function build(string $name, array $options, string $selected, bool $hasError = false): string
    {
        $class = 'form-select' . ($hasError ? ' is-invalid' : '');
        $client = new DropdownClient($options, $selected);
        return (new Dropdown($client))->setName($name)->setId($name)->addClass($class)->render();
    }
}
