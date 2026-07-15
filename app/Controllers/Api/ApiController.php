<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Throwable;
use App\Core\Database;
use App\Core\Http\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use hstanleycrow\EasyPHPFormValidator\Validator;
use hstanleycrow\EasyPHPDBCore\Connection\IConnection;

/**
 * Base for JSON API controllers. Actions return a JsonResponse (which the
 * router sends) and are wrapped in handle() so any thrown exception is mapped
 * to a consistent JSON error.
 */
class ApiController
{
    public function __construct(protected Request $request, public string $currentRoute = '')
    {
    }

    /**
     * Shared, lazily-opened database connection.
     */
    protected function db(): IConnection
    {
        return Database::connection();
    }

    /**
     * @return array<string, mixed>
     */
    protected function input(): array
    {
        try {
            return $this->request->toArray();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param callable(): JsonResponse $action
     */
    protected function handle(callable $action): JsonResponse
    {
        try {
            return $action();
        } catch (Throwable $e) {
            return (new ExceptionHandler())->toResponse($e);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $rules
     * @param array<string, string> $messages
     */
    protected function validate(array $data, array $rules, array $messages = []): void
    {
        Validator::validate($data, $rules, $messages);
    }
}
