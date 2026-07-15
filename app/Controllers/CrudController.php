<?php

declare(strict_types=1);

namespace App\Controllers;

use hstanleycrow\EasyPHPDatatableCRUD\DatatableUIBuilder;

/**
 * Builds a datatable UI for an admin listing from a definition name. The
 * definition resolves through DT_DEFINITIONS_NAMESPACE (see .env).
 */
class CrudController
{
    public function generateDatatable(string $DTDefinition): DatatableUIBuilder
    {
        $datatable = new DatatableUIBuilder($DTDefinition, []);
        $datatable->setAddButtonClass(\App\Components\Buttons\AddButton::class);
        $datatable->setAjaxUrl('/datatable_handler.php');

        return $datatable;
    }
}
