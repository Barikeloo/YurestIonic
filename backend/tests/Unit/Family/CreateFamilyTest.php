<?php

namespace Tests\Unit\Family;

use App\Family\Application\CreateFamily\CreateFamily;
use App\Family\Application\CreateFamily\CreateFamilyResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateFamilyTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_family_and_saves_it(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Family $family): bool {
                return $family->name() === 'Comida' && $family->isActive();
            }));

        $createFamily = new CreateFamily($repository);
        $response = $createFamily('Comida');

        $this->assertInstanceOf(CreateFamilyResponse::class, $response);
        $this->assertSame('Comida', $response->name);
        $this->assertTrue($response->active);
    }
}
