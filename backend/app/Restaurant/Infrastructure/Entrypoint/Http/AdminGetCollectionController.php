<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\GetAdminRestaurantCollection\GetAdminRestaurantCollection;
use App\Restaurant\Domain\Exception\LinkedRestaurantNotFoundException;
use App\Restaurant\Domain\Exception\NotAuthenticatedException;
use App\Restaurant\Infrastructure\Entrypoint\Http\Requests\GetAdminRestaurantCollectionRequest;
use Illuminate\Http\JsonResponse;

final class AdminGetCollectionController
{
    public function __construct(
        private GetAdminRestaurantCollection $getAdminRestaurantCollection,
    ) {}

    public function __invoke(GetAdminRestaurantCollectionRequest $request): JsonResponse
    {
        try {
            $response = ($this->getAdminRestaurantCollection)($request->toCommand());
        } catch (NotAuthenticatedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        } catch (LinkedRestaurantNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse([
            'data' => $response->data(),
        ], 200);
    }
}
