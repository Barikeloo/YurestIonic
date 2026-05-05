<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetQuickUsers\GetQuickUsers;
use App\User\Infrastructure\Entrypoint\Http\Requests\GetQuickUsersRequest;
use Illuminate\Http\JsonResponse;

final class GetQuickUsersController
{
    public function __construct(
        private GetQuickUsers $getQuickUsers,
    ) {}

    public function __invoke(GetQuickUsersRequest $request): JsonResponse
    {
        try {
            $response = ($this->getQuickUsers)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray());
    }
}
