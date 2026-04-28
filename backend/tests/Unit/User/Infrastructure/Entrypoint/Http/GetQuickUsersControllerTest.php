<?php

namespace Tests\Unit\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetQuickUsers\GetQuickUsers;
use App\User\Application\GetQuickUsers\GetQuickUsersResponse;
use App\User\Infrastructure\Entrypoint\Http\GetQuickUsersController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class GetQuickUsersControllerTest extends TestCase
{
    public function test_returns_quick_users_response(): void
    {
        $getQuickUsersResponse = $this->createMock(GetQuickUsersResponse::class);
        $getQuickUsersResponse->method('toArray')->willReturn([
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
        ]);

        $getQuickUsers = $this->createMock(GetQuickUsers::class);
        $getQuickUsers->expects($this->once())
            ->method('__invoke')
            ->with('device-123', null)
            ->willReturn($getQuickUsersResponse);

        $controller = new GetQuickUsersController($getQuickUsers);
        $request = $this->getMockBuilder(Request::class)
            ->disableOriginalConstructor()
            ->addMethods(['validate'])
            ->getMock();
        $request->expects($this->once())
            ->method('validate')
            ->with([
                'device_id' => ['required', 'string', 'max:100'],
                'restaurant_uuid' => ['nullable', 'string', 'uuid'],
            ])
            ->willReturn(['device_id' => 'device-123']);

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
