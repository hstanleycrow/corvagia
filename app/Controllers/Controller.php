<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Route;
use App\Core\Database;
use hstanleycrow\EasyPHPFormValidator\Validator;
use Symfony\Component\HttpFoundation\Request;
use hstanleycrow\EasyPHPDBCore\Connection\IConnection;
use hstanleycrow\EasyPHPFormValidator\ValidationException;

class Controller
{

    public function __construct(protected Request $request, public string $currentRoute)
    {
    }

    /**
     * Shared, lazily-opened database connection. Call it only where a model is
     * needed so DB-less pages never open a connection.
     */
    protected function db(): IConnection
    {
        return Database::connection();
    }

    protected function validate(array $rules, array $messages = []): void
    {
        $data = $this->request->request->all();

        $_SESSION['formData'] = $data;
        try {
            Validator::validate($data, $rules, $messages);
        } catch (ValidationException $e) {
            $errors = [];
            foreach ($e->getErrors() as $field => $errorsList) {
                foreach ($errorsList as $error) {
                    $errors[$field] = $error;
                }
            }
            $_SESSION['errors'] = $errors;
            $this->route($this->currentRoute, $data);
        }
    }

    protected function route(string $routeName, ?array $params = []): void
    {
        $url = Route::getUrlFromName($routeName, $params ?? []);
        redirect($url);
    }
}
