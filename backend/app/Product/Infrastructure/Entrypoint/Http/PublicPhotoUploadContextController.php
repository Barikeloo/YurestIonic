<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\GetProductPhotoUploadContext\GetProductPhotoUploadContext;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Infrastructure\Entrypoint\Http\Requests\PublicPhotoUploadContextRequest;
use Illuminate\Http\JsonResponse;

final class PublicPhotoUploadContextController
{
    public function __construct(
        private GetProductPhotoUploadContext $getContext,
    ) {}

    public function __invoke(PublicPhotoUploadContextRequest $request): JsonResponse
    {
        try {
            $response = ($this->getContext)($request->toCommand());
        } catch (ProductPhotoUploadTokenNotFoundException|ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ProductPhotoUploadTokenExpiredException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 410);
        } catch (ProductPhotoUploadTokenAlreadyUsedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
