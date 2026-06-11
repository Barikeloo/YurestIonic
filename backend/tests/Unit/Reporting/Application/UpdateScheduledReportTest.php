<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\UpdateScheduledReport\UpdateScheduledReport;
use App\Reporting\Application\UpdateScheduledReport\UpdateScheduledReportCommand;
use App\Reporting\Domain\Exception\InvalidScheduleException;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class UpdateScheduledReportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private UpdateScheduledReport $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->useCase = new UpdateScheduledReport($this->repository);
    }

    public function test_updates_report_fields_and_recalculates_next_run(): void
    {
        $uuid = 'report-uuid-123';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn(['uuid' => $uuid, 'name' => 'Old Name']);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($uuid, Mockery::on(fn (array $data): bool => (
                $data['report_type'] === 'products'
                && $data['format'] === 'CSV'
                && $data['frequency'] === 'daily'
                && $data['time'] === '14:00'
                && $data['name'] === 'Updated Name'
                && $data['recipients'] === ['new@test.com']
                && isset($data['next_run_at'])
            )));

        ($this->useCase)(new UpdateScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
            reportType: 'products',
            format: 'CSV',
            frequency: 'daily',
            time: '14:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['new@test.com'],
            name: 'Updated Name',
            active: true,
        ));
    }

    public function test_updates_with_inactive_sets_far_future_next_run(): void
    {
        $uuid = 'report-uuid-inactive';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(['uuid' => $uuid, 'name' => 'Some Report']);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($uuid, Mockery::on(fn (array $data): bool => (
                $data['active'] === false
                && $data['next_run_at'] === '9999-12-31 23:59:59'
            )));

        ($this->useCase)(new UpdateScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['a@b.com'],
            name: 'Inactive',
            active: false,
        ));
    }

    public function test_throws_exception_when_not_found(): void
    {
        $uuid = 'nonexistent-uuid';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn(null);

        $this->repository->shouldNotReceive('update');

        $this->expectException(ScheduledReportNotFoundException::class);
        $this->expectExceptionMessage("Scheduled report with UUID {$uuid} not found.");

        ($this->useCase)(new UpdateScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['a@b.com'],
            name: 'Test',
            active: true,
        ));
    }

    public function test_rejects_invalid_report_type(): void
    {
        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(['uuid' => 'some-uuid']);

        $this->repository->shouldNotReceive('update');

        $this->expectException(InvalidScheduleException::class);

        ($this->useCase)(new UpdateScheduledReportCommand(
            restaurantId: 1,
            uuid: 'some-uuid',
            reportType: 'INVALID',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['a@b.com'],
            name: 'Test',
            active: true,
        ));
    }

    public function test_rejects_empty_recipients_on_update(): void
    {
        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(['uuid' => 'some-uuid']);

        $this->repository->shouldNotReceive('update');

        $this->expectException(InvalidScheduleException::class);

        ($this->useCase)(new UpdateScheduledReportCommand(
            restaurantId: 1,
            uuid: 'some-uuid',
            reportType: 'daily',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: [],
            name: 'Test',
            active: true,
        ));
    }
}
