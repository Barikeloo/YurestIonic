<?php

namespace Tests\Unit\Family\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Family\Application\DeleteFamily\DeleteFamily;
use App\Family\Application\DeleteFamily\DeleteFamilyCommand;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use Mockery;
use PHPUnit\Framework\TestCase;

class DeleteFamilyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_deletes_family(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $family = Family::dddCreate(FamilyName::create('To delete'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($family->id()->value())
            ->andReturn($family);

        $repository->shouldReceive('deleteById')
            ->once()
            ->with($family->id()->value())
            ->andReturnTrue();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new DeleteFamily($repository, $auditRecorder);

        $useCase(new DeleteFamilyCommand(
            id: $family->id()->value(),
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('deleteById');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new DeleteFamily($repository, $auditRecorder);

        $this->expectException(FamilyNotFoundException::class);

        $useCase(new DeleteFamilyCommand(
            id: 'non-existent-id',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
