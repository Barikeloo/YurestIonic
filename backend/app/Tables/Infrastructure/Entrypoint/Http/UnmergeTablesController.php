<?php

namespace App\Tables\Infrastructure\Entrypoint\Http;

use App\Tables\Application\UnmergeTables\UnmergeTables;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Exception\TablesWithOpenOrdersException;
use App\Tables\Infrastructure\Entrypoint\Http\Requests\UnmergeTablesRequest;
use Illuminate\Http\JsonResponse;

final class UnmergeTablesController
{
    public function __construct(
        private readonly UnmergeTables $unmergeTables,
    ) {}

    public function __invoke(UnmergeTablesRequest $request): JsonResponse
    {
        try {
            $response = ($this->unmergeTables)($request->toCommand());
        } catch (TableNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (TablesWithOpenOrdersException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
