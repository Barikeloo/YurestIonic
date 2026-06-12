<?php

namespace Tests\Feature\Reporting;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ReportFileGeneratorTest extends TestCase
{
    use RefreshDatabase;

    // getDashboardData uses HOUR(), a MySQL function SQLite can't run. The daily
    // report (PDF+CSV) and the families CSV go through it, so those are exercised
    // by the Playwright e2e against real MySQL instead. taxes is covered below
    // (needs quarter/year).
    /** @var list<string> */
    private const PDF_SAFE_TYPES = ['products', 'families', 'cash', 'tips'];

    /** @var list<string> */
    private const CSV_SAFE_TYPES = ['products', 'cash', 'tips'];

    private function generator(): ReportFileGeneratorInterface
    {
        return app(ReportFileGeneratorInterface::class);
    }

    public function test_generates_a_valid_pdf_for_every_report_type(): void
    {
        $tenant = $this->createTenantSession('admin');
        $range = DateRange::fromFrequency('daily');

        foreach (self::PDF_SAFE_TYPES as $type) {
            $result = $this->generator()->generate($tenant["restaurant_id"], $type, "PDF", $range);

            $this->assertSame('application/pdf', $result['mimeType'], "mime for {$type}");
            $this->assertStringEndsWith('.pdf', $result['filename'], "filename for {$type}");
            $this->assertStringStartsWith('%PDF', $result['contents'], "pdf magic bytes for {$type}");
        }
    }

    public function test_generates_a_csv_for_every_report_type(): void
    {
        $tenant = $this->createTenantSession('admin');
        $range = DateRange::fromFrequency('daily');

        foreach (self::CSV_SAFE_TYPES as $type) {
            $result = $this->generator()->generate($tenant['restaurant_id'], $type, 'CSV', $range);

            $this->assertStringContainsString('text/csv', $result['mimeType'], "mime for {$type}");
            $this->assertStringEndsWith('.csv', $result['filename'], "filename for {$type}");
            $this->assertNotSame('', $result['contents'], "csv body for {$type}");
        }
    }

    public function test_generates_taxes_report_in_both_formats(): void
    {
        $tenant = $this->createTenantSession('admin');
        $range = DateRange::fromFrequency('quarterly');

        $pdf = $this->generator()->generate($tenant['restaurant_id'], 'taxes', 'PDF', $range, 'T1', 2026);
        $this->assertSame('application/pdf', $pdf['mimeType']);
        $this->assertStringStartsWith('%PDF', $pdf['contents']);

        $csv = $this->generator()->generate($tenant['restaurant_id'], 'taxes', 'CSV', $range, 'T1', 2026);
        $this->assertStringContainsString('text/csv', $csv['mimeType']);
        $this->assertNotSame('', $csv['contents']);
    }
}
