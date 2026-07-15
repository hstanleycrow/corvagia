<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Users;

use Models\User;
use App\Core\Template;
use App\Core\FlashMessages;
use App\Controllers\Controller;
use App\Components\Dropdowns\Dropdown;
use App\Components\Dropdowns\DropdownClient;

class UsersCreateController extends Controller
{
    private string $title = 'Administrador de Usuarios | ' . BUSINESS_NAME;
    private string $h1    = 'Agregar Usuario';

    public function showForm(): void
    {
        $activeSelected  = $_SESSION['formData']['active']  ?? 'S';
        $isAdminSelected = $_SESSION['formData']['isAdmin'] ?? 'N';

        Template::render('admin/sections/Users/UserForm.tpl', [
            'title'                  => $this->title,
            'h1'                     => $this->h1,
            'action'                 => 'add',
            'formAction'             => '/admin/user/agregar/',
            'record'                 => [],
            'activeDropdown'         => $this->buildEnumDropdown('active', ['S' => 'Activo', 'N' => 'Inactivo'], $activeSelected),
            'isAdminDropdown'        => $this->buildEnumDropdown('isAdmin', ['S' => 'Administrador', 'N' => 'Usuario'], $isAdminSelected),
            'useDataTablesResources' => false,
        ]);
    }

    private function buildEnumDropdown(string $name, array $options, string $selected): string
    {
        $class = 'form-select' . (!empty($_SESSION['errors'][$name]) ? ' is-invalid' : '');
        $client = new DropdownClient($options, $selected);
        return (new Dropdown($client))->setName($name)->setId($name)->addClass($class)->render();
    }

    public function create(): void
    {
        $validator = new UsersFormValidator();
        $this->validate($validator->getRules(false), $validator->getMessages(false));

        $userModel = new User($this->db());

        if ($userModel->isAvailableUser($this->request->get('username'))) {
            FlashMessages::set('danger', 'El nombre de usuario ya está en uso.');
            $this->route('admin.showUserAddForm');
            return;
        }

        $userModel->create([
            'name'     => $this->request->get('name'),
            'username' => $this->request->get('username'),
            'password' => $this->request->get('password'),
            'active'   => $this->request->get('active'),
            'isAdmin'  => $this->request->get('isAdmin'),
        ]);

        FlashMessages::set('success', 'El usuario se ha creado correctamente.');
        unset($_SESSION['formData']);
        $this->route('admin.usersList');
    }
}
