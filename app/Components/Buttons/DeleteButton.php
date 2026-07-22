<?php

declare(strict_types=1);

namespace App\Components\Buttons;

use hstanleycrow\EasyPHPWebComponents\Icon;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\BaseLink;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\LinkClient;

/**
 * Per-row "delete" action button. Referenced from a datatable definition via
 * the `buttonClass` key in getButtons(); the builder passes the row href to the
 * constructor. Give the link a `.btn-danger` class if you want the bundled
 * SweetAlert confirm (see the dashboard layout) to intercept it.
 *
 * Reusable component: change the CSS class, icon or text here, or subclass it.
 */
class DeleteButton extends BaseLink
{
    public function __construct(string $href, ?string $label = null)
    {
        $content = (new Icon('fa-solid fa-trash', $label ?: 'Delete'))->render();
        parent::__construct(new LinkClient($href, $content));
        $this->addClass('btn btn-danger btn-sm');
    }
}
