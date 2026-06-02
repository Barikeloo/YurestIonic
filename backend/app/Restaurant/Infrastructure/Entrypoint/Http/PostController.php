<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\CreateRestaurant\CreateRestaurant;
use App\Restaurant\Domain\Exception\NotSuperAdminException;
use App\Restaurant\Domain\Exception\TaxIdAlreadyExistsException;
use App\Restaurant\Domain\Exception\TaxIdDoesNotExistException;
use App\Restaurant\Infrastructure\Entrypoint\Http\Requests\CreateRestaurantRequest;
use App\User\Domain\Exception\PinAlreadyInUseException;
use Illuminate\Http\JsonResponse;

final class PostController
{
    public function __construct(
        private readonly CreateRestaurant $createRestaurant,
    ) {}

    public function __invoke(CreateRestaurantRequest $request): JsonResponse
    {
        $superAdminUuid = $request->session()->get('super_admin_id');

        try {
            $response = ($this->createRestaurant)(
                $request->toCommand(),
                is_string($superAdminUuid) ? $superAdminUuid : null,
            );
        } catch (NotSuperAdminException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 403);
        } catch (TaxIdAlreadyExistsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (TaxIdDoesNotExistException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (PinAlreadyInUseException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
