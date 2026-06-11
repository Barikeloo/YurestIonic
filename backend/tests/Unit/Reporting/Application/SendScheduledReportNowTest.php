<?php

namespace Tests\Unit\Reporting\Application;

use App\Mail\ScheduledReportMail;
use App\Reporting\Application\SendScheduledReportNow\SendScheduledReportNow;
use App\Reporting\Application\SendScheduledReportNow\SendScheduledReportNowCommand;
use App\Reporting\Application\SendScheduledReportNow\SendScheduledReportNowResponse;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class SendScheduledReportNowTest extends TestCase
{
    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private ReportFileGeneratorInterface&MockInterface $fileGenerator;
    private SendScheduledReportNow $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->fileGenerator = Mockery::mock(ReportFileGeneratorInterface::class);

        $this->useCase = new SendScheduledReportNow(
            $this->repository,
            $this->fileGenerator,
        );

        Mail::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_sends_report_and_returns_response(): void
    {
        $uuid = 'report-uuid-send';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn([
                'uuid' => $uuid,
                'report_type' => 'daily',
                'format' => 'PDF',
                'frequency' => 'daily',
                'recipients' => ['admin@test.com'],
            ]);

        $this->fileGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'contents' => 'pdf-binary-data',
                'filename' => 'daily-report.pdf',
                'mimeType' => 'application/pdf',
            ]);

        $response = ($this->useCase)(new SendScheduledReportNowCommand(
            restaurantId: 1,
            uuid: $uuid,
        ));

        $this->assertInstanceOf(SendScheduledReportNowResponse::class, $response);
        $this->assertSame($uuid, $response->uuid);
        $this->assertSame('Resumen diario', $response->reportName);
        $this->assertSame([
            'uuid' => $uuid,
            'report_name' => 'Resumen diario',
        ], $response->toArray());

        Mail::assertSent(ScheduledReportMail::class);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $uuid = 'nonexistent-uuid';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->fileGenerator->shouldNotReceive('generate');

        $this->expectException(ScheduledReportNotFoundException::class);

        ($this->useCase)(new SendScheduledReportNowCommand(
            restaurantId: 1,
            uuid: $uuid,
        ));
    }
}
