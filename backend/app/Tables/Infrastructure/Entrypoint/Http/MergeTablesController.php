<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\MergeTables\MergeTables;
use App\Tables\Domain\Exception\MinimumTwoTablesRequiredException;
use App\Tables\Domain\Exception\TablesAlreadyMergedException;
use App\Tables\Domain\Exception\TablesNotInSameZoneException;
use App\Tables\Domain\Exception\TablesNotFoundException;
use App\Tables\Domain\Exception\TablesWithOpenOrdersException;
use App\Tables\Infrastructure\Entrypoint\Http\Requests\MergeTablesRequest;
use Illuminate\Http\JsonResponse;

final class MergeTablesController
{
    public function __construct(
        private readonly MergeTables $mergeTables,
    ) {}

    public function __invoke(MergeTablesRequest $request): JsonResponse
    {
        try {
            $response = ($this->mergeTables)($request->toCommand());
        } catch (MinimumTwoTablesRequiredException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch (TablesNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (TablesAlreadyMergedException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch (TablesNotInSameZoneException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch (TablesWithOpenOrdersException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
