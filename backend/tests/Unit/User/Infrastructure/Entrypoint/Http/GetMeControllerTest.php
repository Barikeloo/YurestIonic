<?php

namespace Tests\Unit\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetMe\GetMe;
use App\User\Application\GetMe\GetMeCommand;
use App\User\Application\GetMe\GetMeResponse;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\GetMeController;
use App\User\Infrastructure\Entrypoint\Http\Requests\GetMeRequest;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class GetMeControllerTest extends TestCase
{
    public function test_returns_unauthenticated_if_no_user_id_in_session(): void
    {
        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->never())->method('__invoke');

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn(null);
        $session->expects($this->never())->method('forget');

        $request = new GetMeRequest();
        $request->setLaravelSession($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_returns_unauthenticated_if_user_not_found(): void
    {
        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(fn (GetMeCommand $c) => $c->userId === 'user-id'))
            ->willThrowException(UserNotFoundException::withId('user-id'));

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn('user-id');
        $session->expects($this->once())->method('forget')->with('auth_user_id');

        $request = new GetMeRequest();
        $request->setLaravelSession($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_returns_successful_response(): void
    {
        $getMeResponse = new GetMeResponse(
            id: 'uuid',
            name: 'Test User',
            email: 'test@example.com',
            role: 'admin',
            restaurantId: 'rest-uuid',
            restaurantName: 'Test Restaurant',
        );

        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(fn (GetMeCommand $c) => $c->userId === 'user-id'))
            ->willReturn($getMeResponse);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn('user-id');
        $session->expects($this->never())->method('forget');

        $request = new GetMeRequest();
        $request->setLaravelSession($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertTrue($data['success']);
        $this->assertEquals('uuid', $data['id']);
        $this->assertEquals('Test User', $data['name']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals('admin', $data['role']);
        $this->assertEquals('rest-uuid', $data['restaurant_id']);
        $this->assertEquals('Test Restaurant', $data['restaurant_name']);
    }
}
