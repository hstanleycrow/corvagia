<?php

declare(strict_types=1);

namespace App\Components\Buttons;

use hstanleycrow\EasyPHPWebComponents\Icon;
use hstanleycrow\EasyPHPWebComponents\Button;

/**
 * Form submit button. Renders a Bootstrap `<button type="submit">` with a Font
 * Awesome icon. Reusable component: change the CSS class, icon or text here.
 */
class SaveButton
{
    public function __construct(private string $text = 'Save')
    {
    }

    public function render(): string
    {
        $content = (new Icon('fa-solid fa-floppy-disk', $this->text))->render();
        return (new Button($content, 'submit'))
            ->addClass('btn btn-success')
            ->setId('btn-save')
            ->render();
    }
}
