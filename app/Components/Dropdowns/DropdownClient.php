<?php

declare(strict_types=1);

namespace App\Components\Dropdowns;

use hstanleycrow\EasyPHPWebComponents\Select;

class DropdownClient implements DropdownInterface
{
    protected Select $dropdown;

    public function __construct(array $options = [], ?string $selected = null)
    {
        $this->dropdown = new Select($options, $selected);
    }

    public function addClass(string $class): self
    {
        $this->dropdown->addClass($class);
        return $this;
    }

    public function render(): string
    {
        return $this->dropdown->render();
    }

    public function setId(string $id): self
    {
        $this->dropdown->setId($id);
        return $this;
    }
    public function setName(string $name): self
    {
        $this->dropdown->setName($name);
        return $this;
    }
    public function setDisabled(bool $disabled): self
    {
        $this->dropdown->setDisabled($disabled);
        return $this;
    }
    public function setMultiple(bool $multiple): self
    {
        $this->dropdown->setMultiple($multiple);
        return $this;
    }
}
