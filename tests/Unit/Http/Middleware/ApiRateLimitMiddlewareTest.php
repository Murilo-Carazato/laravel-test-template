<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\ApiRateLimitMiddleware;
use App\Domains\Core\Services\RateLimitService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Mockery;
use Carbon\Carbon;
use App\Models\User;

class ApiRateLimitMiddlewareTest extends TestCase
{
    protected $rateLimitService;
    protected ApiRateLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a proper mock
        $this->rateLimitService = Mockery::mock(RateLimitService::class);
        
        // Bind the mock to the container
        $this->app->instance(RateLimitService::class, $this->rateLimitService);
        
        // Create middleware with the mocked service
        $this->middleware = new ApiRateLimitMiddleware($this->rateLimitService);
    }

    /** @test */
    public function it_allows_request_when_within_limit(): void
    {
        // Arrange
        $request = Request::create('/test-endpoint', 'GET');
        $user = User::factory()->make(['id' => 1]);
        $request->setUserResolver(fn () => $user);

        // Mocks para o RateLimitService
        $this->rateLimitService->shouldReceive('checkRateLimit')
            ->once()
            ->with($request, $user)
            ->andReturn([false, 99, Carbon::now()->addSeconds(60)]); // false = não excedeu

        $this->rateLimitService->shouldReceive('getRateLimitKey')
            ->once()
            ->with($request, $user)
            ->andReturn('user:1:endpoint:hash');
            
        $this->rateLimitService->shouldReceive('getLimitForKey')
            ->once()
            ->with('user:1:endpoint:hash', $user)
            ->andReturn(100);

        $next = function ($req) {
            return new Response('OK', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
        $this->assertEquals(100, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(99, $response->headers->get('X-RateLimit-Remaining'));
        $this->assertNotNull($response->headers->get('X-RateLimit-Reset'));
    }

    /** @test */
    public function it_blocks_request_when_exceeds_limit(): void
    {
        // Arrange
        $request = Request::create('/test-endpoint', 'GET');
        $user = User::factory()->make(['id' => 1]);
        $request->setUserResolver(fn () => $user);
        $resetTime = Carbon::now()->addSeconds(30);

        // Mocks para o RateLimitService
        $this->rateLimitService->shouldReceive('checkRateLimit')
            ->once()
            ->with($request, $user)
            ->andReturn([true, 0, $resetTime]); // true = excedeu

        // O 'next' não deve ser chamado
        $next = function ($req) {
            $this->fail('Next middleware should not be called when limit is exceeded.');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Limite de requisições excedido. Por favor, aguarde antes de tentar novamente.', $responseData['message']);
        $this->assertEquals($resetTime->diffInSeconds(Carbon::now()), $responseData['retry_after']);
        $this->assertNull($response->headers->get('X-RateLimit-Limit')); // Headers não devem ser adicionados se bloqueado antes de $next
    }

    /** @test */
    public function it_handles_guest_requests_rate_limiting(): void
    {
        // Arrange
        $request = Request::create('/public-endpoint', 'GET');
        $resetTime = Carbon::now()->addSeconds(60);

        // Mocks para o RateLimitService
        $this->rateLimitService->shouldReceive('checkRateLimit')
            ->once()
            ->with($request, null) // Sem usuário autenticado
            ->andReturn([false, 29, $resetTime]);

        $this->rateLimitService->shouldReceive('getRateLimitKey')
            ->once()
            ->with($request, null)
            ->andReturn('ip:hash:endpoint:hash'); // Chave baseada em IP
            
        $this->rateLimitService->shouldReceive('getLimitForKey')
            ->once()
            ->with('ip:hash:endpoint:hash', null)
            ->andReturn(30); // Limite padrão para não autenticados

        $next = function ($req) {
            return new Response('OK', 200);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(30, $response->headers->get('X-RateLimit-Limit'));
        $this->assertEquals(29, $response->headers->get('X-RateLimit-Remaining'));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}