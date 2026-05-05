<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccess;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccessCommand;
use App\User\Application\CreateRestaurantUser\CreateRestaurantUser;
use App\User\Domain\Exception\ForbiddenRestaurantAccessException;
use App\User\Domain\Exception\NotAuthenticatedException;
use App\User\Domain\Exception\PinAlreadyInUseException;
use App\User\Domain\Exception\RestaurantNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\CreateRestaurantUserRequest;
use Illuminate\Http\JsonResponse;

final class AdminPostController
{
    public function __construct(
        private AuthorizeRestaurantAccess $authorizeRestaurantAccess,
        private CreateRestaurantUser $createRestaurantUser,
    ) {}

    public function __invoke(CreateRestaurantUserRequest $request, string $uuid): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            $authUserUuid = $request->session()->get('auth_user_id');

            if (! is_string($authUserUuid) || $authUserUuid === '') {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Not authenticated.',
                ], 401);
            }

            try {
                $this->authorizeRestaurantAccess->__invoke(new AuthorizeRestaurantAccessCommand(
                    authUserUuid: $authUserUuid,
                    targetRestaurantUuid: $uuid,
                ));
            } catch (NotAuthenticatedException $e) {
                return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 401);
            } catch (RestaurantNotFoundException $e) {
                return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 404);
            } catch (ForbiddenRestaurantAccessException $e) {
                return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 403);
            } catch (\Throwable $e) {
                report($e);

                return new JsonResponse(['success' => false, 'message' => 'Internal error.'], 500);
            }
        }

        try {
            $response = ($this->createRestaurantUser)($request->toCommand($uuid));
        } catch (PinAlreadyInUseException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['success' => false, 'message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
