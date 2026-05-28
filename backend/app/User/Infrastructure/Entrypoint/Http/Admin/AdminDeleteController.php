<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccess;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccessCommand;
use App\User\Application\DeleteRestaurantUser\DeleteRestaurantUser;
use App\User\Domain\Exception\ForbiddenRestaurantAccessException;
use App\User\Domain\Exception\NotAuthenticatedException;
use App\User\Domain\Exception\RestaurantNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\DeleteRestaurantUserRequest;
use Illuminate\Http\JsonResponse;

final class AdminDeleteController
{
    public function __construct(
        private AuthorizeRestaurantAccess $authorizeRestaurantAccess,
        private DeleteRestaurantUser $deleteRestaurantUser,
    ) {}

    public function __invoke(DeleteRestaurantUserRequest $request, string $uuid, string $userUuid): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $authUserUuid = null;

        if (! is_string($superAdminUuid) || $superAdminUuid === '') {
            $superAdminUuid = null;
            $authUserUuid = $request->session()->get('auth_user_id');

            if (! is_string($authUserUuid) || $authUserUuid === '') {
                return new JsonResponse([
                    'message' => 'Not authenticated.',
                ], 401);
            }

            try {
                $this->authorizeRestaurantAccess->__invoke(new AuthorizeRestaurantAccessCommand(
                    authUserUuid: $authUserUuid,
                    targetRestaurantUuid: $uuid,
                ));
            } catch (NotAuthenticatedException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 401);
            } catch (RestaurantNotFoundException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 404);
            } catch (ForbiddenRestaurantAccessException $e) {
                return new JsonResponse(['message' => $e->getMessage()], 403);
            } catch (\Throwable $e) {
                report($e);

                return new JsonResponse(['message' => 'Internal error.'], 500);
            }
        }

        try {
            $response = ($this->deleteRestaurantUser)($request->toCommand($uuid, $userUuid, $authUserUuid, $superAdminUuid));
        } catch (UserNotFoundException $e) {
            return new JsonResponse(['message' => 'User not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
