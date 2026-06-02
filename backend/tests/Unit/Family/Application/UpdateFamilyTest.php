<?php

namespace Tests\Unit\Family\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Family\Application\UpdateFamily\UpdateFamily;
use App\Family\Application\UpdateFamily\UpdateFamilyCommand;
use App\Family\Application\UpdateFamily\UpdateFamilyResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use Mockery;
use PHPUnit\Framework\TestCase;

class UpdateFamilyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_family_name(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $family = Family::dddCreate(FamilyName::create('Original'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($family->id()->value())
            ->andReturn($family);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (Family $f): bool => $f->name()->value() === 'Updated'));

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new UpdateFamily($repository, $auditRecorder);

        $response = $useCase(new UpdateFamilyCommand(
            id: $family->id()->value(),
            name: 'Updated',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(UpdateFamilyResponse::class, $response);
        $this->assertSame('Updated', $response->name);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('save');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new UpdateFamily($repository, $auditRecorder);

        $this->expectException(FamilyNotFoundException::class);

        $useCase(new UpdateFamilyCommand(
            id: 'non-existent-id',
            name: 'Updated',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
