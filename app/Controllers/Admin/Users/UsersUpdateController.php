<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Users;

use Models\User;
use App\Core\Template;
use App\Core\FlashMessages;
use App\Controllers\Controller;
use App\Components\Dropdowns\Dropdown;
use App\Components\Dropdowns\DropdownClient;

class UsersUpdateController extends Controller
{
    private string $title = 'Administrador de Usuarios | ' . BUSINESS_NAME;
    private string $h1    = 'Editar Usuario';

    public function showForm(int $id): void
    {
        $userModel    = new User($this->db());
        $record       = $userModel->getById($id);
        $record['id'] = $id;

        unset($_SESSION['formData']);

        Template::render('admin/sections/Users/UserForm.tpl', [
            'title'                  => $this->title,
            'h1'                     => $this->h1,
            'action'                 => 'edit',
            'formAction'             => "/admin/user/editar/{$id}/",
            'record'                 => $record,
            'activeDropdown'         => $this->buildEnumDropdown('active', ['S' => 'Activo', 'N' => 'Inactivo'], $record['active'] ?? 'S'),
            'isAdminDropdown'        => $this->buildEnumDropdown('isAdmin', ['S' => 'Administrador', 'N' => 'Usuario'], $record['isAdmin'] ?? 'N'),
            'useDataTablesResources' => false,
        ]);
    }

    private function buildEnumDropdown(string $name, array $options, string $selected): string
    {
        $class = 'form-select' . (!empty($_SESSION['errors'][$name]) ? ' is-invalid' : '');
        $client = new DropdownClient($options, $selected);
        return (new Dropdown($client))->setName($name)->setId($name)->addClass($class)->render();
    }

    public function save(): void
    {
        $validator = new UsersFormValidator();
        $this->validate($validator->getRules(true), $validator->getMessages(true));

        $id       = (int) $this->request->get('id');
        $password = trim($this->request->get('password') ?? '');

        $fields = [
            'name'     => $this->request->get('name'),
            'username' => $this->request->get('username'),
            'active'   => $this->request->get('active'),
            'isAdmin'  => $this->request->get('isAdmin'),
        ];

        // Only update the password when a new one was provided.
        if (!empty($password)) {
            $fields['password'] = User::passwordHash($password . AUTH_SALT);
        }

        (new User($this->db()))->update($fields, ['id' => $id]);

        FlashMessages::set('success', 'El usuario se ha actualizado correctamente.');
        $this->route('admin.usersList');
    }
}
