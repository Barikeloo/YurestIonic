<?php

namespace Tests\Unit\Family\Application;

use App\Family\Application\ListFamilies\ListFamilies;
use App\Family\Application\ListFamilies\ListFamiliesCommand;
use App\Family\Application\ListFamilies\ListFamiliesResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListFamiliesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_all_families(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $family1 = Family::dddCreate(FamilyName::create('Entrantes'));
        $family2 = Family::dddCreate(FamilyName::create('Postres'));

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([$family1, $family2]);

        $useCase = new ListFamilies($repository);

        $response = $useCase(new ListFamiliesCommand(
            includeDeleted: false,
            onlyActive: false,
        ));

        $this->assertInstanceOf(ListFamiliesResponse::class, $response);
        $this->assertCount(2, $response->toArray());
        $this->assertSame('Entrantes', $response->toArray()[0]['name']);
        $this->assertSame('Postres', $response->toArray()[1]['name']);
    }

    public function test_filters_only_active(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $family1 = Family::fromPersistence(
            id: '00000000-0000-4000-8000-000000000001',
            name: 'Entrantes',
            active: true,
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );
        $family2 = Family::fromPersistence(
            id: '00000000-0000-4000-8000-000000000002',
            name: 'Postres',
            active: false,
            createdAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
            updatedAt: new \DateTimeImmutable('2026-01-01 00:00:00'),
        );

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([$family1, $family2]);

        $useCase = new ListFamilies($repository);

        $response = $useCase(new ListFamiliesCommand(
            includeDeleted: false,
            onlyActive: true,
        ));

        $this->assertCount(1, $response->toArray());
        $this->assertSame('Entrantes', $response->toArray()[0]['name']);
    }

    public function test_returns_empty_list(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([]);

        $useCase = new ListFamilies($repository);

        $response = $useCase(new ListFamiliesCommand(
            includeDeleted: false,
            onlyActive: false,
        ));

        $this->assertInstanceOf(ListFamiliesResponse::class, $response);
        $this->assertEmpty($response->toArray());
    }
}
