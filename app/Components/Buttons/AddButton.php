<?php

declare(strict_types=1);

namespace App\Components\Buttons;

use hstanleycrow\EasyPHPWebComponents\Icon;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\BaseLink;
use hstanleycrow\EasyPHPDatatableCRUD\Buttons\LinkClient;

/**
 * Table-level "create" button for a datatable listing. The CRUD builder
 * instantiates it as `new AddButton($definitionName)` via setAddButtonClass(),
 * so the constructor receives the definition name and builds the href.
 *
 * Reusable component: change the CSS class, icon or text here, or subclass it.
 */
class AddButton extends BaseLink
{
    public function __construct(?string $DTDefinition)
    {
        $href = '/admin/' . $DTDefinition . '/agregar/'; // create route (matches routes/admin.php)
        $content = (new Icon('fa-solid fa-plus', 'Add'))->render();
        parent::__construct(new LinkClient($href, $content));
        $this->addClass('btn btn-success');
    }
}
