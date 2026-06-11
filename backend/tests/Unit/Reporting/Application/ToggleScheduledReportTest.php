<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\ToggleScheduledReport\ToggleScheduledReport;
use App\Reporting\Application\ToggleScheduledReport\ToggleScheduledReportCommand;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ToggleScheduledReportTest extends TestCase
{
    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private ToggleScheduledReport $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->useCase = new ToggleScheduledReport($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_toggles_active_to_inactive(): void
    {
        $uuid = 'toggle-uuid';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn([
                'uuid' => $uuid,
                'active' => true,
                'frequency' => 'daily',
                'time' => '08:00',
                'weekday' => null,
                'day_of_month' => null,
            ]);

        $this->repository
            ->shouldReceive('setActive')
            ->once()
            ->with($uuid, false);

        $this->repository->shouldNotReceive('update');

        $result = ($this->useCase)(new ToggleScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
        ));

        $this->assertSame(['uuid' => $uuid, 'active' => false], $result);
    }

    public function test_toggles_inactive_to_active_and_recalculates_next_run(): void
    {
        $uuid = 'toggle-uuid-2';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn([
                'uuid' => $uuid,
                'active' => false,
                'frequency' => 'daily',
                'time' => '08:00',
                'weekday' => null,
                'day_of_month' => null,
            ]);

        $this->repository
            ->shouldReceive('setActive')
            ->once()
            ->with($uuid, true);

        $this->repository
            ->shouldReceive('update')
            ->once()
            ->with($uuid, Mockery::on(fn (array $data): bool => isset($data['next_run_at'])));

        $result = ($this->useCase)(new ToggleScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
        ));

        $this->assertSame(['uuid' => $uuid, 'active' => true], $result);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $uuid = 'nonexistent-uuid';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn(null);

        $this->repository->shouldNotReceive('setActive');
        $this->repository->shouldNotReceive('update');

        $this->expectException(ScheduledReportNotFoundException::class);
        $this->expectExceptionMessage("Scheduled report with UUID {$uuid} not found.");

        ($this->useCase)(new ToggleScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
        ));
    }
}
