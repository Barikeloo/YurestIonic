<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\DispatchDueScheduledReports\DispatchDueScheduledReports;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Illuminate\Support\Facades\Mail;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DispatchDueScheduledReportsTest extends TestCase
{
    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private ReportFileGeneratorInterface&MockInterface $fileGenerator;
    private DispatchDueScheduledReports $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->fileGenerator = Mockery::mock(ReportFileGeneratorInterface::class);

        $this->useCase = new DispatchDueScheduledReports(
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

    public function test_dispatches_due_reports_and_updates_next_run(): void
    {
        $now = new \DateTimeImmutable('2026-06-11 13:00:00');
        $uuid = 'due-report-1';

        $this->repository
            ->shouldReceive('listDue')
            ->once()
            ->with($now)
            ->andReturn([
                [
                    'uuid' => $uuid,
                    'restaurant_id' => 1,
                    'report_type' => 'daily',
                    'format' => 'PDF',
                    'frequency' => 'daily',
                    'time' => '08:00',
                    'weekday' => null,
                    'day_of_month' => null,
                    'recipients' => ['admin@test.com'],
                ],
            ]);

        $this->fileGenerator
            ->shouldReceive('generate')
            ->once()
            ->andReturn([
                'contents' => 'binary-data',
                'filename' => 'report.pdf',
                'mimeType' => 'application/pdf',
            ]);

        $this->repository
            ->shouldReceive('markRun')
            ->once()
            ->with(
                $uuid,
                Mockery::type(\DateTimeImmutable::class),
                Mockery::type(\DateTimeImmutable::class),
            );

        $sent = ($this->useCase)($now);

        $this->assertSame(1, $sent);
    }

    public function test_dispatches_zero_when_no_due_reports(): void
    {
        $now = new \DateTimeImmutable('2026-06-11 13:00:00');

        $this->repository
            ->shouldReceive('listDue')
            ->once()
            ->with($now)
            ->andReturn([]);

        $this->fileGenerator->shouldNotReceive('generate');

        $sent = ($this->useCase)($now);

        $this->assertSame(0, $sent);
    }

    public function test_continues_on_failure_and_logs_error(): void
    {
        $now = new \DateTimeImmutable('2026-06-11 13:00:00');

        $this->repository
            ->shouldReceive('listDue')
            ->once()
            ->andReturn([
                [
                    'uuid' => 'failing-report',
                    'restaurant_id' => 1,
                    'report_type' => 'daily',
                    'format' => 'PDF',
                    'frequency' => 'daily',
                    'time' => '08:00',
                    'weekday' => null,
                    'day_of_month' => null,
                    'recipients' => ['admin@test.com'],
                ],
            ]);

        $this->fileGenerator
            ->shouldReceive('generate')
            ->once()
            ->andThrow(new \RuntimeException('PDF generation failed'));

        $this->repository->shouldNotReceive('markRun');

        $sent = ($this->useCase)($now);

        $this->assertSame(0, $sent);
    }

    public function test_dispatches_multiple_reports(): void
    {
        $now = new \DateTimeImmutable('2026-06-11 13:00:00');

        $this->repository
            ->shouldReceive('listDue')
            ->once()
            ->andReturn([
                ['uuid' => 'r1', 'restaurant_id' => 1, 'report_type' => 'daily', 'format' => 'PDF', 'frequency' => 'daily', 'time' => '08:00', 'weekday' => null, 'day_of_month' => null, 'recipients' => ['a@b.com']],
                ['uuid' => 'r2', 'restaurant_id' => 1, 'report_type' => 'products', 'format' => 'CSV', 'frequency' => 'weekly', 'time' => '10:00', 'weekday' => 1, 'day_of_month' => null, 'recipients' => ['c@d.com']],
            ]);

        $this->fileGenerator
            ->shouldReceive('generate')
            ->twice()
            ->andReturn(['contents' => 'data', 'filename' => 'r.pdf', 'mimeType' => 'application/pdf']);

        $this->repository
            ->shouldReceive('markRun')
            ->twice();

        $sent = ($this->useCase)($now);

        $this->assertSame(2, $sent);
    }
}
