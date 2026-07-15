<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Users;

use Models\User;
use App\Core\FlashMessages;
use App\Controllers\Controller;

class UsersDeleteController extends Controller
{
    public function delete(int $id): void
    {
        (new User($this->db()))->delete(['id' => $id]);

        FlashMessages::set('success', 'El usuario se ha eliminado correctamente.');
        $this->route('admin.usersList');
    }
}
