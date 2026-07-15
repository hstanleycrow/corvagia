<?php

declare(strict_types=1);

namespace App\Core\Http;

use Throwable;
use PDOException;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Core\Exceptions\RouteNotFoundException;
use App\Core\Exceptions\ResourceNotFoundException;
use App\Core\Exceptions\ControllerNotFoundException;
use hstanleycrow\EasyPHPDBCore\Exception\QueryException;
use hstanleycrow\EasyPHPFormValidator\ValidationException;
use hstanleycrow\EasyPHPDBCore\Exception\ConnectionException;

/**
 * Translates any Throwable into a JSON error response with an appropriate HTTP
 * status. Internal details are hidden outside of a testing/dev environment.
 */
final class ExceptionHandler
{
    public function toResponse(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException =>
                ApiResponse::error('validation_failed', 'The given data was invalid.', 422, $this->validationDetails($e)),

            $e instanceof ResourceNotFoundException =>
                ApiResponse::error('not_found', $e->getMessage() !== '' ? $e->getMessage() : 'Resource not found.', 404),

            $e instanceof RouteNotFoundException, $e instanceof ControllerNotFoundException =>
                ApiResponse::error('not_found', 'Resource not found.', 404),

            $e instanceof QueryException =>
                $this->isIntegrityViolation($e)
                    ? ApiResponse::error('conflict', 'The request conflicts with existing data.', 409)
                    : ApiResponse::error('server_error', $this->safeMessage($e), 500),

            $e instanceof ConnectionException =>
                ApiResponse::error('service_unavailable', 'Service temporarily unavailable.', 503),

            default =>
                ApiResponse::error('server_error', $this->safeMessage($e), 500),
        };
    }

    public function isApiRequest(string $path): bool
    {
        return str_starts_with($path, '/api/');
    }

    private function isIntegrityViolation(QueryException $e): bool
    {
        $previous = $e->getPrevious();
        $sqlState = $previous instanceof PDOException
            ? (string) ($previous->errorInfo[0] ?? $previous->getCode())
            : (string) ($previous?->getCode() ?? $e->getCode());

        return str_starts_with($sqlState, '23');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function validationDetails(ValidationException $e): array
    {
        $details = [];
        foreach ($e->getErrors() as $field => $messages) {
            $details[(string) $field] = array_values(array_map('strval', (array) $messages));
        }

        return $details;
    }

    private function safeMessage(Throwable $e): string
    {
        $isDebug = str_contains((string) ($_ENV['ENVIRONMENT'] ?? ''), 'testing');

        return $isDebug ? $e->getMessage() : 'Internal server error.';
    }
}
