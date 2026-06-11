<?php

namespace Tests\Unit\Reporting\Application;

use App\Reporting\Application\DeleteScheduledReport\DeleteScheduledReport;
use App\Reporting\Application\DeleteScheduledReport\DeleteScheduledReportCommand;
use App\Reporting\Domain\Exception\ScheduledReportNotFoundException;
use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DeleteScheduledReportTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ScheduledReportRepositoryInterface&MockInterface $repository;
    private DeleteScheduledReport $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(ScheduledReportRepositoryInterface::class);
        $this->useCase = new DeleteScheduledReport($this->repository);
    }

    public function test_deletes_existing_report(): void
    {
        $uuid = 'report-to-delete';

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(1, $uuid)
            ->andReturn(['uuid' => $uuid]);

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($uuid);

        ($this->useCase)(new DeleteScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
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

        $this->repository->shouldNotReceive('delete');

        $this->expectException(ScheduledReportNotFoundException::class);
        $this->expectExceptionMessage("Scheduled report with UUID {$uuid} not found.");

        ($this->useCase)(new DeleteScheduledReportCommand(
            restaurantId: 1,
            uuid: $uuid,
        ));
    }
}
