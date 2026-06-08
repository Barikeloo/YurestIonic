<?php

namespace App\Product\Infrastructure\Entrypoint\Http;

use App\Product\Application\GenerateProductPhotoUploadToken\GenerateProductPhotoUploadToken;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Infrastructure\Entrypoint\Http\Requests\GeneratePhotoUploadTokenRequest;
use Illuminate\Http\JsonResponse;

final class GeneratePhotoUploadTokenController
{
    public function __construct(
        private GenerateProductPhotoUploadToken $generateToken,
    ) {}

    public function __invoke(GeneratePhotoUploadTokenRequest $request): JsonResponse
    {
        try {
            $response = ($this->generateToken)($request->toCommand());
        } catch (ProductNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
