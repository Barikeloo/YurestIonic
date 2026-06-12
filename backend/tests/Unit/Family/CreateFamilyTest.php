<?php

namespace Tests\Unit\Family;

use App\Family\Application\CreateFamily\CreateFamily;
use App\Family\Application\CreateFamily\CreateFamilyCommand;
use App\Family\Application\CreateFamily\CreateFamilyResponse;
use App\Family\Domain\Entity\Family;
use App\Family\Domain\Event\FamilyCreated;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateFamilyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_invoke_creates_family_and_publishes_event(): void
    {
        $repository = Mockery::mock(FamilyRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Family $family): bool => $family->name()->value() === 'Comida' && $family->isActive()
        ));

        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(FamilyCreated::class));

        $createFamily = new CreateFamily($repository, $eventBus);

        $response = $createFamily(new CreateFamilyCommand(name: 'Comida'));

        $this->assertInstanceOf(CreateFamilyResponse::class, $response);
        $this->assertSame('Comida', $response->name);
        $this->assertTrue($response->active);
    }
}
