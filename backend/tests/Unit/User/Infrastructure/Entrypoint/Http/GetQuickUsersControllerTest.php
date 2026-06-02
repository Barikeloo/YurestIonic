<?php

namespace Tests\Unit\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetQuickUsers\GetQuickUsers;
use App\User\Application\GetQuickUsers\GetQuickUsersCommand;
use App\User\Application\GetQuickUsers\GetQuickUsersResponse;
use App\User\Infrastructure\Entrypoint\Http\GetQuickUsersController;
use App\User\Infrastructure\Entrypoint\Http\Requests\GetQuickUsersRequest;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class GetQuickUsersControllerTest extends TestCase
{
    public function test_returns_quick_users_response(): void
    {
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

        $getQuickUsers = $this->createMock(GetQuickUsers::class);
        $getQuickUsers->expects($this->once())
            ->method('__invoke')
            ->with($this->isInstanceOf(GetQuickUsersCommand::class))
            ->willReturn($responseData);

        $request = $this->getMockBuilder(GetQuickUsersRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['input'])
            ->getMock();
        $request->expects($this->exactly(2))->method('input')->willReturnMap([
            ['device_id', null, 'device-123'],
            ['restaurant_uuid', null, null],
        ]);

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
