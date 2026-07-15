<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use Models\User;
use App\Resources\UserResource;
use App\Core\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Core\Exceptions\ResourceNotFoundException;

final class UsersController extends ApiController
{
    private const DEFAULT_PER_PAGE = 25;
    private const MAX_PER_PAGE = 100;

    public function index(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $page = max(1, (int) $this->request->query->get('page', '1'));
            $perPage = max(1, min(self::MAX_PER_PAGE, (int) $this->request->query->get('per_page', (string) self::DEFAULT_PER_PAGE)));

            $users = $this->users();
            $total = $users->countAll();
            $data = array_map(
                static fn (array $row): array => UserResource::fromRow($row)->toArray(),
                $users->paginate($page, $perPage)
            );

            return ApiResponse::success($data, 200, [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => (int) ceil($total / $perPage),
            ]);
        });
    }

    public function show(int $id): JsonResponse
    {
        return $this->handle(function () use ($id): JsonResponse {
            $row = $this->users()->getById($id);
            if ($row === null) {
                throw new ResourceNotFoundException("User {$id} not found.");
            }

            return ApiResponse::success(UserResource::fromRow($row)->toArray());
        });
    }

    public function store(): JsonResponse
    {
        return $this->handle(function (): JsonResponse {
            $data = $this->input();
            $this->validate($data, [
                'name'     => 'required',
                'username' => 'required',
                'password' => 'required',
            ]);

            $users = $this->users();
            $id = $users->create([
                'name'     => (string) $data['name'],
                'username' => (string) $data['username'],
                'password' => (string) $data['password'],
                'active'   => (string) ($data['active'] ?? 'S'),
            ])->lastInsertId();

            return ApiResponse::success(UserResource::fromRow($users->getById((int) $id))->toArray(), 201);
        });
    }

    public function update(int $id): JsonResponse
    {
        return $this->handle(function () use ($id): JsonResponse {
            $users = $this->users();
            if ($users->getById($id) === null) {
                throw new ResourceNotFoundException("User {$id} not found.");
            }

            $data = $this->input();
            $this->validate($data, ['name' => 'required']);
            $users->update(['name' => (string) $data['name']], ['id' => $id]);

            return ApiResponse::success(UserResource::fromRow($users->getById($id))->toArray());
        });
    }

    public function destroy(int $id): JsonResponse
    {
        return $this->handle(function () use ($id): JsonResponse {
            $users = $this->users();
            if ($users->getById($id) === null) {
                throw new ResourceNotFoundException("User {$id} not found.");
            }

            $users->delete(['id' => $id]);

            return ApiResponse::noContent();
        });
    }

    private function users(): User
    {
        return new User($this->db());
    }
}
