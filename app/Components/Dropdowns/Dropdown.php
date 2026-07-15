<?php

declare(strict_types=1);

namespace App\Components\Dropdowns;

class Dropdown
{

    public function __construct(private DropdownClient $dropdownClient)
    {
    }

    public function render(): string
    {
        return $this->dropdownClient->render();
    }

    public function addClass(string $class): self
    {
        $this->dropdownClient->addClass($class);
        return $this;
    }


    public function setId(string $id): self
    {
        $this->dropdownClient->setId($id);
        return $this;
    }
    public function setName(string $name): self
    {
        $this->dropdownClient->setName($name);
        return $this;
    }
    public function setDisabled(bool $disabled): self
    {
        $this->dropdownClient->setDisabled($disabled);
        return $this;
    }
    public function setMultiple(bool $multiple): self
    {
        $this->dropdownClient->setMultiple($multiple);
        return $this;
    }
}
