<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\RefreshTokenMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken; // Importar PersonalAccessToken
use App\Models\User; // Importar User
use Carbon\Carbon; // Importar Carbon
use Mockery; // Importar Mockery

class RefreshTokenMiddlewareTest extends TestCase
{
    protected RefreshTokenMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new RefreshTokenMiddleware();
        Auth::shouldReceive('check')->andReturn(false); // Default para não autenticado
    }

    /** @test */
    public function it_does_nothing_if_user_is_not_authenticated(): void
    {
        // Arrange
        $request = Request::create('/');
        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        Auth::shouldReceive('check')->andReturn(false); // Garante que não está autenticado

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_does_nothing_if_token_is_not_found(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer invalid_token');

        // Mock PersonalAccessToken para não encontrar o token
        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('invalid_token')
            ->andReturn(null);

        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_does_not_refresh_token_if_not_expiring_soon(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer valid_token');

        // Token criado há 10 minutos (TTL padrão 60 min, threshold 30 min) -> não precisa de refresh
        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->created_at = Carbon::now()->subMinutes(10);
        config(['sanctum.expiration' => 60]); // 60 minutos de expiração

        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('valid_token')
            ->andReturn($accessToken);

        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_refreshes_token_if_expiring_soon(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer old_token');

        // Token criado há 40 minutos (TTL padrão 60 min, threshold 30 min) -> precisa de refresh
        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->created_at = Carbon::now()->subMinutes(40);
        config(['sanctum.expiration' => 60]); // 60 minutos de expiração

        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('old_token')
            ->andReturn($accessToken);
        
        // Mock do createToken para o User model
        $user->shouldReceive('createToken')
             ->once()
             ->with('api-refresh')
             ->andReturn((object)['plainTextToken' => 'new_fresh_token']);

        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('new_fresh_token', $response->headers->get('X-New-Token'));
    }

    /** @test */
    public function it_works_with_custom_sanctum_expiration_config(): void
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]);
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        $request = Request::create('/');
        $request->headers->set('Authorization', 'Bearer test_token');

        // Configura TTL de Sanctum para 30 minutos
        config(['sanctum.expiration' => 30]);

        // Token criado há 10 minutos -> não precisa de refresh (threshold ainda é 30 min, mas o token total é 30)
        // Significa que se expira em 20 min, ainda não está "soon" (menor que 30 min)
        $accessToken = Mockery::mock(PersonalAccessToken::class);
        $accessToken->created_at = Carbon::now()->subMinutes(1); // Expira em 29 min (quase agora)
        
        Mockery::mock('alias:Laravel\Sanctum\PersonalAccessToken')
            ->shouldReceive('findToken')
            ->once()
            ->with('test_token')
            ->andReturn($accessToken);

        // Mock do createToken
        $user->shouldReceive('createToken')
             ->once()
             ->andReturn((object)['plainTextToken' => 'another_new_token']);


        $next = function ($req) {
            return new JsonResponse(['status' => 'ok']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert: Deve refreshar se o token expirar antes do threshold
        // created_at (1 min atrás) + expiration (30 min) = expira em 29 min a partir de agora
        // 29 min < 30 min (threshold) => precisa de refresh
        $this->assertEquals('another_new_token', $response->headers->get('X-New-Token'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}