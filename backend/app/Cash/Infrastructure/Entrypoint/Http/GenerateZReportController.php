<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\GenerateZReport\GenerateZReport;
use App\Cash\Domain\Exception\CashSessionCannotGenerateZReportException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\ZReportAlreadyExistsException;
use App\Cash\Infrastructure\Entrypoint\Http\Requests\GenerateZReportRequest;
use Illuminate\Http\JsonResponse;

final class GenerateZReportController
{
    public function __construct(
        private readonly GenerateZReport $generateZReport,
    ) {}

    public function __invoke(GenerateZReportRequest $request): JsonResponse
    {
        try {
            $response = ($this->generateZReport)($request->toCommand());
        } catch (CashSessionNotFoundException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch (ZReportAlreadyExistsException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (CashSessionCannotGenerateZReportException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
