<?php

namespace Tests\Unit\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetMe\GetMe;
use App\User\Application\GetMe\GetMeResponse;
use App\User\Infrastructure\Entrypoint\Http\GetMeController;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class GetMeControllerTest extends TestCase
{
    public function test_returns_unauthenticated_if_no_user_id_in_session(): void
    {
        $getMe = $this->createMock(GetMe::class);
        $controller = new GetMeController($getMe);
        $request = $this->createMock(Request::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn(null);
        $session->expects($this->never())->method('forget');
        $request->expects($this->once())->method('session')->willReturn($session);

        $response = $controller->__invoke($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_returns_unauthenticated_if_get_me_returns_null(): void
    {
        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->once())->method('__invoke')->with('user-id')->willReturn(null);
        $controller = new GetMeController($getMe);
        $request = $this->createMock(Request::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn('user-id');
        $session->expects($this->once())->method('forget')->with('auth_user_id');
        $request->expects($this->exactly(2))->method('session')->willReturn($session);

        $response = $controller->__invoke($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($response->getData(true)['success']);
    }

    public function test_returns_successful_response(): void
    {
        $getMeResponse = $this->createMock(GetMeResponse::class);
        $getMeResponse->method('toArray')->willReturn([
            'success' => true,
            'id' => 'uuid',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
            'restaurant_id' => 'rest-uuid',
            'restaurant_name' => 'Test Restaurant',
        ]);

        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->once())->method('__invoke')->with('user-id')->willReturn($getMeResponse);
        $controller = new GetMeController($getMe);
        $request = $this->createMock(Request::class);

        $session = $this->createMock(Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn('user-id');
        $session->expects($this->never())->method('forget');
        $request->expects($this->once())->method('session')->willReturn($session);

        $response = $controller->__invoke($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->getData(true)['success']);
        $this->assertEquals('Test User', $response->getData(true)['name']);
    }
}
