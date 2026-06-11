<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Entrypoint\Http;

use App\Mail\TaxReportMail;
use App\Reporting\Application\GetTaxReport\GetTaxReport;
use App\Reporting\Application\GetTaxReport\GetTaxReportCommand;
use App\Reporting\Infrastructure\Entrypoint\Http\Requests\GetTaxReportRequest;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;

final readonly class SendTaxReportController
{
    public function __construct(private GetTaxReport $useCase) {}

    public function __invoke(GetTaxReportRequest $request): JsonResponse
    {
        $restaurantId = app(TenantContext::class)->restaurantId();

        if ($restaurantId === null) {
            return response()->json(['error' => 'No restaurant context'], 403);
        }

        $email = (string) $request->input('email', '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }

        try {
            $response = ($this->useCase)(new GetTaxReportCommand(
                restaurantId: $restaurantId,
                period:       (string) $request->validated()['period'],
                quarter:      $request->validatedQuarter(),
            ));

            $data = $response->toArray();
            $q    = $data['quarterly'];
            $rest = $data['restaurant'];
            $rates = $q['rates'];
            $totalBase = array_sum(array_column($rates, 'base'));
            $totalTax  = array_sum(array_column($rates, 'tax'));

            Mail::to($email)->send(new TaxReportMail(
                legalName:    $rest['legal_name'],
                businessName: $rest['name'],
                taxId:        $rest['tax_id'],
                period:       $q['period'],
                rates:        $rates,
                totalBase:    $totalBase,
                totalTax:     $totalTax,
            ));

            return response()->json(['message' => 'Email sent']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Throwable) {
            return response()->json(['error' => 'Internal error'], 500);
        }
    }
}
