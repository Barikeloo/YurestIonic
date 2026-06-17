<?php

declare(strict_types=1);

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\UploadProductPhotoDirectly\UploadProductPhotoDirectly;
use App\Product\Domain\Exception\InvalidProductPhotoException;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Entrypoint\Http\Requests\DirectProductPhotoUploadRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class DirectProductPhotoUploadController
{
    public function __construct(
        private UploadProductPhotoDirectly $uploadPhoto,
    ) {}

    public function __invoke(DirectProductPhotoUploadRequest $request): JsonResponse
    {
        try {
            $response = DB::transaction(fn () => ($this->uploadPhoto)($request->toCommand()));
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (InvalidProductPhotoException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
