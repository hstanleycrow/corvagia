<?php

declare(strict_types=1);

namespace App\Controllers\Auth;

use Models\User;
use App\Core\Csrf;
use App\Core\Route;
use App\Core\Template;
use App\Core\FlashMessages;
use App\Controllers\Controller;

class LoginController extends Controller
{
    public function showForm(): void
    {
        if (isLogged()) {
            redirect(Route::getUrlFromName('admin.dashboard'));
        }

        $csrf = (new Csrf())->initToken();

        Template::render('auth/login', [
            'title'     => 'Ingreso | ' . BUSINESS_NAME,
            'csrfToken' => $csrf->getToken(),
        ]);
    }

    public function login(): void
    {
        if (!Csrf::validate((string) $this->request->get('csrf_token'))) {
            FlashMessages::set('danger', 'Token de seguridad inválido. Intenta de nuevo.');
            $this->route('login');
            return;
        }

        $username = (string) $this->request->get('username');
        $password = (string) $this->request->get('password');

        $user = (new User($this->db()))->findByUsername($username);

        if ($user === null || $user['active'] !== 'S' || !User::isValidPassword($password, $user['password'])) {
            FlashMessages::set('danger', 'Usuario o contraseña incorrectos.');
            $this->route('login');
            return;
        }

        Csrf::clearCsrfToken();
        $_SESSION['isLogged'] = true;
        $_SESSION['userdat']  = $user;

        $this->route('admin.dashboard');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        redirect(Route::getUrlFromName('login'));
    }
}
