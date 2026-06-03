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
use Mockery;
use PHPUnit\Framework\TestCase;

class GetMeControllerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_unauthenticated_if_no_user_id_in_session(): void
    {
        $getMe = Mockery::mock(GetMe::class);
        $getMe->shouldNotReceive('__invoke');

        $session = Mockery::mock(Session::class);
        $session->shouldReceive('get')->once()->with('auth_user_id')->andReturn(null);

        $request = Mockery::mock(GetMeRequest::class);
        $request->shouldReceive('session')->once()->andReturn($session);

        $response = (new GetMeController($getMe))($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertSame('Not authenticated.', $response->getData(true)['message']);
    }

    public function test_returns_unauthenticated_if_user_not_found(): void
    {
        $getMe = Mockery::mock(GetMe::class);
        $getMe->shouldReceive('__invoke')
            ->once()
            ->andThrow(UserNotFoundException::withId('user-id'));

        $session = Mockery::mock(Session::class);
        $session->shouldReceive('get')->once()->with('auth_user_id')->andReturn('user-id');
        $session->shouldReceive('forget')->once()->with('auth_user_id');

        $request = Mockery::mock(GetMeRequest::class);
        $request->shouldReceive('session')->twice()->andReturn($session);
        $request->shouldReceive('toCommand')->once()->with('user-id')->andReturn(new GetMeCommand(userId: 'user-id'));

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

        $getMe = Mockery::mock(GetMe::class);
        $getMe->shouldReceive('__invoke')->once()->andReturn($getMeResponse);

        $session = Mockery::mock(Session::class);
        $session->shouldReceive('get')->once()->with('auth_user_id')->andReturn('user-id');
        $session->shouldNotReceive('forget');

        $request = Mockery::mock(GetMeRequest::class);
        $request->shouldReceive('session')->once()->andReturn($session);
        $request->shouldReceive('toCommand')->once()->with('user-id')->andReturn(new GetMeCommand(userId: 'user-id'));

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
