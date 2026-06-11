<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\CreateScheduledReport\CreateScheduledReport;
use App\Reporting\Application\CreateScheduledReport\CreateScheduledReportCommand;
use App\Reporting\Application\CreateScheduledReport\CreateScheduledReportResponse;
use App\Reporting\Domain\Exception\InvalidScheduleException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CreateScheduledReportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private CreateScheduledReport $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->useCase = new CreateScheduledReport($this->repository);
    }

    public function test_creates_daily_report(): void
    {
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (array $data): bool => (
                $data['report_type'] === 'daily'
                && $data['format'] === 'PDF'
                && $data['frequency'] === 'daily'
                && $data['time'] === '08:00'
                && $data['weekday'] === null
                && $data['day_of_month'] === null
                && $data['recipients'] === ['admin@test.com']
                && $data['name'] === 'Daily Report'
                && $data['active'] === true
                && $data['restaurant_id'] === 1
                && isset($data['next_run_at'])
            )))
            ->andReturn('generated-uuid-123');

        $response = ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Daily Report',
            active: true,
            createdByUserUuid: 'user-uuid-1',
        ));

        $this->assertInstanceOf(CreateScheduledReportResponse::class, $response);
        $this->assertSame('generated-uuid-123', $response->uuid);
        $this->assertSame(['uuid' => 'generated-uuid-123'], $response->toArray());
    }

    public function test_creates_weekly_report_with_weekday(): void
    {
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (array $data): bool => (
                $data['frequency'] === 'weekly'
                && $data['weekday'] === 1
                && $data['day_of_month'] === null
            )))
            ->andReturn('weekly-uuid');

        $response = ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'products',
            format: 'CSV',
            frequency: 'weekly',
            time: '10:00',
            weekday: 1,
            dayOfMonth: null,
            recipients: ['a@b.com', 'c@d.com'],
            name: 'Weekly Products',
            active: true,
            createdByUserUuid: null,
        ));

        $this->assertSame('weekly-uuid', $response->uuid);
    }

    public function test_creates_monthly_report_with_day_of_month(): void
    {
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (array $data): bool => (
                $data['frequency'] === 'monthly'
                && $data['weekday'] === null
                && $data['day_of_month'] === 15
            )))
            ->andReturn('monthly-uuid');

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'taxes',
            format: 'PDF',
            frequency: 'monthly',
            time: '12:00',
            weekday: null,
            dayOfMonth: 15,
            recipients: ['tax@test.com'],
            name: 'Monthly Tax',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_creates_inactive_report_with_future_next_run(): void
    {
        $this->repository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (array $data): bool => (
                $data['active'] === false
                && isset($data['next_run_at'])
            )))
            ->andReturn('inactive-uuid');

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Inactive Report',
            active: false,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_invalid_report_type(): void
    {
        $this->repository->shouldNotReceive('save');

        $this->expectException(InvalidScheduleException::class);
        $this->expectExceptionMessage('Invalid report type: invalid_type');

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'invalid_type',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_invalid_format(): void
    {
        $this->repository->shouldNotReceive('save');

        $this->expectException(InvalidScheduleException::class);

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'DOCX',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_invalid_frequency(): void
    {
        $this->expectException(InvalidScheduleException::class);

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'yearly',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_weekly_without_weekday(): void
    {
        $this->expectException(InvalidScheduleException::class);
        $this->expectExceptionMessage('Weekday (1-7) is required for weekly frequency');

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'weekly',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_weekly_with_out_of_range_weekday(): void
    {
        $this->expectException(InvalidScheduleException::class);

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'weekly',
            time: '08:00',
            weekday: 8,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_monthly_without_day_of_month(): void
    {
        $this->expectException(InvalidScheduleException::class);
        $this->expectExceptionMessage('Day of month (1-28) is required for monthly frequency');

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'monthly',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: ['admin@test.com'],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }

    public function test_rejects_empty_recipients(): void
    {
        $this->expectException(InvalidScheduleException::class);
        $this->expectExceptionMessage('At least one recipient is required');

        ($this->useCase)(new CreateScheduledReportCommand(
            restaurantId: 1,
            reportType: 'daily',
            format: 'PDF',
            frequency: 'daily',
            time: '08:00',
            weekday: null,
            dayOfMonth: null,
            recipients: [],
            name: 'Test',
            active: true,
            createdByUserUuid: null,
        ));
    }
}
