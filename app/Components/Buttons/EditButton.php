<?php

declare(strict_types=1);

namespace App\Components\Buttons;

use hstanleycrow\EasyPHPWebComponents\Icon;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\BaseLink;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\LinkClient;

/**
 * Per-row "edit" action button. Referenced from a datatable definition via the
 * `buttonClass` key in getButtons(); the builder passes the row href (built
 * from the button's `path` + primary key) to the constructor.
 *
 * Reusable component: change the CSS class, icon or text here, or subclass it.
 */
class EditButton extends BaseLink
{
    public function __construct(string $href)
    {
        $content = (new Icon('fa-solid fa-pen-to-square', 'Edit'))->render();
        parent::__construct(new LinkClient($href, $content));
        $this->addClass('btn btn-warning btn-sm');
    }
}
