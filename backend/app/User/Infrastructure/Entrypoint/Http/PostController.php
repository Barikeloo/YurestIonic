<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\CreateUser\CreateUser;
use App\User\Infrastructure\Entrypoint\Http\Requests\CreateUserRequest;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function __construct(
        private CreateUser $createUser,
    ) {}

    public function __invoke(CreateUserRequest $request): JsonResponse
    {
        try {
            $response = ($this->createUser)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
