<?php

namespace Tests\Unit\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetQuickUsers\GetQuickUsers;
use App\User\Application\GetQuickUsers\GetQuickUsersCommand;
use App\User\Application\GetQuickUsers\GetQuickUsersResponse;
use App\User\Infrastructure\Entrypoint\Http\GetQuickUsersController;
use App\User\Infrastructure\Entrypoint\Http\Requests\GetQuickUsersRequest;
use Illuminate\Http\JsonResponse;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetQuickUsersControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_quick_users_response(): void
    {
        $command = new GetQuickUsersCommand(deviceId: 'device-123', restaurantUuid: null);

        $responseData = GetQuickUsersResponse::create([
            [
                'user_uuid' => 'uuid1',
                'name' => 'User 1',
                'role' => 'waiter',
                'restaurant_uuid' => 'rest1',
                'restaurant_name' => 'Rest 1',
                'last_login_at' => '2024-01-01 12:00:00',
            ],
        ]);

        $getQuickUsers = Mockery::mock(GetQuickUsers::class);
        $getQuickUsers->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetQuickUsersCommand::class))
            ->andReturn($responseData);

        $request = Mockery::mock(GetQuickUsersRequest::class);
        $request->shouldReceive('toCommand')->once()->andReturn($command);

        $controller = new GetQuickUsersController($getQuickUsers);
        $response = $controller->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals([
            'users' => [
                [
                    'user_uuid' => 'uuid1',
                    'name' => 'User 1',
                    'role' => 'waiter',
                    'restaurant_uuid' => 'rest1',
                    'restaurant_name' => 'Rest 1',
                    'last_login_at' => '2024-01-01 12:00:00',
                ],
            ],
        ], $response->getData(true));
    }
}
