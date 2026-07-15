<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Users;

use App\Core\Template;
use App\Controllers\Controller;
use App\Controllers\CrudController;

class UsersIndexController extends Controller
{
    private string $title = 'Administrador de Usuarios | ' . BUSINESS_NAME;
    private string $h1    = 'Listado de Usuarios';
    protected string $DTDefinition = 'user';

    public function index(): void
    {
        $datatable = (new CrudController())->generateDatatable($this->DTDefinition);

        Template::render('admin/sections/Users/userList.tpl', [
            'title'                  => $this->title,
            'h1'                     => $this->h1,
            'datatable'              => $datatable,
            'useDataTablesResources' => true,
        ]);
    }
}
