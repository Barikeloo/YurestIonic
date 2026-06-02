<?php

namespace Tests\Unit\Family\Application;

use App\Family\Application\GetFamily\GetFamily;
use App\Family\Application\GetFamily\GetFamilyCommand;
use App\Family\Application\GetFamily\GetFamilyResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Exception\FamilyNotFoundException;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Family\Domain\ValueObject\FamilyName;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetFamilyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_family_when_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $family = Family::dddCreate(FamilyName::create('Entrantes'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($family->id()->value())
            ->andReturn($family);

        $useCase = new GetFamily($repository);

        $response = $useCase(new GetFamilyCommand(id: $family->id()->value()));

        $this->assertInstanceOf(GetFamilyResponse::class, $response);
        $this->assertSame('Entrantes', $response->name);
        $this->assertTrue($response->active);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $useCase = new GetFamily($repository);

        $this->expectException(FamilyNotFoundException::class);

        $useCase(new GetFamilyCommand(id: 'non-existent-id'));
    }
}
