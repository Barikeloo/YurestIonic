<?php

namespace App\User\Infrastructure\Entrypoint\Http\Admin;

use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccess;
use App\User\Application\AuthorizeRestaurantAccess\AuthorizeRestaurantAccessCommand;
use App\User\Application\UpdateRestaurantUser\UpdateRestaurantUser;
use App\User\Domain\Exception\CannotDemoteSelfAdminException;
use App\User\Domain\Exception\ForbiddenRestaurantAccessException;
use App\User\Domain\Exception\NotAuthenticatedException;
use App\User\Domain\Exception\PinAlreadyInUseException;
use App\User\Domain\Exception\RestaurantNotFoundException;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\Requests\UpdateRestaurantUserRequest;
use Illuminate\Http\JsonResponse;

final class AdminPutController
{
    public function __construct(
        private AuthorizeRestaurantAccess $authorizeRestaurantAccess,
        private UpdateRestaurantUser $updateRestaurantUser,
    ) {}

    public function __invoke(UpdateRestaurantUserRequest $request, string $uuid, string $userUuid): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');
        $authUserUuid = null;

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
            $response = ($this->updateRestaurantUser)($request->toCommand($uuid, $userUuid, $authUserUuid));
        } catch (CannotDemoteSelfAdminException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (PinAlreadyInUseException $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 409);
        } catch (UserNotFoundException $e) {
            return new JsonResponse(['success' => false, 'message' => 'User not found.'], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['success' => false, 'message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
