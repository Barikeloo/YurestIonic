<?php

namespace Tests\Unit\Family;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Family\Application\CreateFamily\CreateFamily;
use App\Family\Application\CreateFamily\CreateFamilyCommand;
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
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Family $family): bool {
                return $family->name()->value() === 'Comida' && $family->isActive();
            }));

        $auditRecorder->shouldReceive('record')
            ->once();

        $createFamily = new CreateFamily($repository, $auditRecorder);

        $response = $createFamily(new CreateFamilyCommand(
            name: 'Comida',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(CreateFamilyResponse::class, $response);
        $this->assertSame('Comida', $response->name);
        $this->assertTrue($response->active);
    }
}
