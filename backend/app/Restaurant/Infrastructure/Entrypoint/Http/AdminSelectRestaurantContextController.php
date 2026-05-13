<?php

namespace App\Restaurant\Infrastructure\Entrypoint\Http;

use App\Restaurant\Application\SelectRestaurantContext\SelectRestaurantContext;
use App\Restaurant\Domain\Exception\ForbiddenException;
use App\Restaurant\Domain\Exception\LinkedRestaurantNotFoundException;
use App\Restaurant\Domain\Exception\NotAuthenticatedException;
use App\Restaurant\Domain\Exception\RestaurantNotFoundException;
use App\Restaurant\Infrastructure\Entrypoint\Http\Requests\SelectRestaurantContextRequest;
use Illuminate\Http\JsonResponse;

final class AdminSelectRestaurantContextController
{
    public function __construct(
        private SelectRestaurantContext $selectRestaurantContext,
    ) {}

    public function __invoke(SelectRestaurantContextRequest $request): JsonResponse
    {
        try {
            $response = ($this->selectRestaurantContext)($request->toCommand());
        } catch (RestaurantNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (NotAuthenticatedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 401);
        } catch (LinkedRestaurantNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ForbiddenException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        $request->session()->put('tenant_restaurant_uuid', $response->restaurantUuid());

        return new JsonResponse([
            'success' => true,
            'restaurant_id' => $response->restaurantUuid(),
            'name' => $response->restaurantName(),
        ]);
    }
}
