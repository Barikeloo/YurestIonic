<?php

namespace Tests\Unit\User\Infrastructure\Entrypoint\Http;

use App\User\Application\GetMe\GetMe;
use App\User\Application\GetMe\GetMeCommand;
use App\User\Application\GetMe\GetMeResponse;
use App\User\Domain\Exception\UserNotFoundException;
use App\User\Infrastructure\Entrypoint\Http\GetMeController;
use App\User\Infrastructure\Entrypoint\Http\Requests\GetMeRequest;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\TestCase;

class GetMeControllerTest extends TestCase
{
    public function test_returns_unauthenticated_if_no_user_id_in_session(): void
    {
        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->never())->method('__invoke');

        $request = $this->getMockBuilder(GetMeRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['session'])
            ->getMock();
        $session = $this->createMock(\Illuminate\Contracts\Session\Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn(null);

        $request->expects($this->once())->method('session')->willReturn($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame('Not authenticated.', $response->getData(true)['message']);
    }

    public function test_returns_unauthenticated_if_user_not_found(): void
    {
        $getMe = $this->createMock(GetMe::class);
        $getMe->expects($this->once())
            ->method('__invoke')
            ->willThrowException(UserNotFoundException::withId('user-id'));

        $request = $this->getMockBuilder(GetMeRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['session'])
            ->getMock();
        $session = $this->createMock(\Illuminate\Contracts\Session\Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn('user-id');
        $session->expects($this->once())->method('forget')->with('auth_user_id');

        $request->expects($this->exactly(2))->method('session')->willReturn($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame('Not authenticated.', $response->getData(true)['message']);
    }

    public function test_returns_successful_response(): void
    {
        $getMeResponse = GetMeResponse::create(
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
            ->willReturn($getMeResponse);

        $request = $this->getMockBuilder(GetMeRequest::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['session'])
            ->getMock();
        $session = $this->createMock(\Illuminate\Contracts\Session\Session::class);
        $session->expects($this->once())->method('get')->with('auth_user_id')->willReturn('user-id');
        $session->expects($this->never())->method('forget');

        $request->expects($this->once())->method('session')->willReturn($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertEquals('uuid', $data['id']);
        $this->assertEquals('Test User', $data['name']);
        $this->assertEquals('test@example.com', $data['email']);
        $this->assertEquals('admin', $data['role']);
        $this->assertEquals('rest-uuid', $data['restaurant_id']);
        $this->assertEquals('Test Restaurant', $data['restaurant_name']);
    }
}
