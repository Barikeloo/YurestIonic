<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetMe\GetMe;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\GetMeRequest;
use Illuminate\Http\JsonResponse;

final class GetMeController
{
    public function __construct(
        private GetMe $getMe,
    ) {}

    public function __invoke(GetMeRequest $request): JsonResponse
    {
        $userId = $request->session()->get('auth_user_id');

        if (! is_string($userId) || $userId === '') {
            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        }

        try {
            $response = ($this->getMe)($request->toCommand($userId));
        } catch (UserNotFoundException $e) {
            $request->session()->forget('auth_user_id');

            return new JsonResponse([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse([
                'success' => false,
                'message' => 'Internal error.',
            ], 500);
        }

        return new JsonResponse(array_merge(['success' => true], $response->toArray()), 200);
    }
}
