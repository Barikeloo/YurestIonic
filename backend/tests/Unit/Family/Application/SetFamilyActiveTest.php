<?php

namespace Tests\Unit\Family\Application;

use App\Family\Application\SetFamilyActive\SetFamilyActive;
use App\Family\Application\SetFamilyActive\SetFamilyActiveCommand;
use App\Family\Application\SetFamilyActive\SetFamilyActiveResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use Mockery;
use PHPUnit\Framework\TestCase;

class SetFamilyActiveTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_activates_family(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $family = Family::fromPersistence(
            id: '00000000-0000-4000-8000-000000000001',
            name: 'Entrantes',
            color: null,
            icon: null,
            active: false,
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($family->id()->value())
            ->andReturn($family);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (Family $f): bool => $f->isActive() === true));

        $useCase = new SetFamilyActive($repository);

        $response = $useCase(new SetFamilyActiveCommand(
            id: $family->id()->value(),
            active: true,
        ));

        $this->assertInstanceOf(SetFamilyActiveResponse::class, $response);
        $this->assertTrue($response->active);
    }

    public function test_deactivates_family(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $family = Family::dddCreate(FamilyName::create('Entrantes'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($family->id()->value())
            ->andReturn($family);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (Family $f): bool => $f->isActive() === false));

        $useCase = new SetFamilyActive($repository);

        $response = $useCase(new SetFamilyActiveCommand(
            id: $family->id()->value(),
            active: false,
        ));

        $this->assertInstanceOf(SetFamilyActiveResponse::class, $response);
        $this->assertFalse($response->active);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('save');

        $useCase = new SetFamilyActive($repository);

        $this->expectException(FamilyNotFoundException::class);

        $useCase(new SetFamilyActiveCommand(id: 'non-existent-id', active: true));
    }
}
