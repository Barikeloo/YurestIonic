<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\UploadProductPhoto\UploadProductPhoto;
use App\Product\Domain\Exception\InvalidProductPhotoException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Infrastructure\Entrypoint\Http\Requests\UploadProductPhotoRequest;
use Illuminate\Http\JsonResponse;

final class PublicPhotoUploadController
{
    public function __construct(
        private UploadProductPhoto $uploadPhoto,
    ) {}

    public function __invoke(UploadProductPhotoRequest $request): JsonResponse
    {
        try {
            $response = ($this->uploadPhoto)($request->toCommand());
        } catch (ProductPhotoUploadTokenNotFoundException|ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ProductPhotoUploadTokenExpiredException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 410);
        } catch (ProductPhotoUploadTokenAlreadyUsedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (InvalidProductPhotoException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
