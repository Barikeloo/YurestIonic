<?php

namespace Tests\Unit\Family\Application;

use App\Family\Application\ListActiveFamilies\ListActiveFamilies;
use App\Family\Application\ListActiveFamilies\ListActiveFamiliesCommand;
use App\Family\Application\ListFamilies\ListFamilies;
use App\Family\Application\ListFamilies\ListFamiliesResponse;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListActiveFamiliesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_delegates_to_list_families_with_active_filter(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $listFamilies = new ListFamilies($repository);

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([]);

        $useCase = new ListActiveFamilies($listFamilies);

        $response = $useCase(new ListActiveFamiliesCommand());

        $this->assertInstanceOf(ListFamiliesResponse::class, $response);
        $this->assertEmpty($response->toArray());
    }
}
