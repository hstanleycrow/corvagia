<?php

declare(strict_types=1);

namespace App\Components\Buttons;

use hstanleycrow\EasyPHPWebComponents\Icon;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\BaseLink;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\LinkClient;

/**
 * Cancel link for a form (goes back to the listing). Renders a Bootstrap
 * `<a class="btn ...">`. Reusable component: change the CSS class, icon or text
 * here, or subclass it.
 */
class CancelButton extends BaseLink
{
    public function __construct(string $href, ?string $label = null)
    {
        $content = (new Icon('fa-solid fa-xmark', $label ?: 'Cancel'))->render();
        parent::__construct(new LinkClient($href, $content));
        $this->addClass('btn btn-danger');
    }
}
