<?php

declare(strict_types=1);

namespace App\Controllers\Admin\Users;

class UsersFormValidator
{
    public function getRules(bool $isEdit = false): array
    {
        $rules = [
            'name'     => 'required|string|min:3|max:100',
            'username' => 'required|string|min:3|max:100',
            'active'   => 'required|in:S,N',
            'isAdmin'  => 'required|in:S,N',
        ];

        // No confirmed on edit: the edit form has no password_confirmation field, so
        // nothing would ever be there to match against — this is intentional, not an
        // omission.
        $rules['password'] = $isEdit
            ? 'nullable|string|min:6|max:100'
            : 'required|string|min:6|max:100|confirmed';

        return $rules;
    }

    public function getMessages(bool $isEdit = false): array
    {
        $messages = [
            'name.required'     => 'El campo "Nombre" es obligatorio',
            'name.min'          => 'El campo "Nombre" debe tener un mínimo de 3 caracteres',
            'name.max'          => 'El campo "Nombre" debe tener un máximo de 100 caracteres',
            'username.required' => 'El campo "Usuario" es obligatorio',
            'username.min'      => 'El campo "Usuario" debe tener un mínimo de 3 caracteres',
            'username.max'      => 'El campo "Usuario" debe tener un máximo de 100 caracteres',
            'active.required'   => 'El campo "Activo" es obligatorio',
            'active.in'         => 'El campo "Activo" tiene un valor inválido',
            'isAdmin.required'  => 'El campo "Admin" es obligatorio',
            'isAdmin.in'        => 'El campo "Admin" tiene un valor inválido',
        ];

        $messages['password.min'] = 'La contraseña debe tener un mínimo de 6 caracteres';

        if (!$isEdit) {
            $messages['password.required']  = 'El campo "Contraseña" es obligatorio';
            $messages['password.confirmed'] = 'Las contraseñas no coinciden';
        }

        return $messages;
    }
}
