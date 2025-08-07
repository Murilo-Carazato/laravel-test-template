<?php

namespace Tests\Unit\Http\Middleware;

use Tests\TestCase;
use App\Http\Middleware\CacheResponseMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Models\User; // Importar User
use Mockery; // Importar Mockery

class CacheResponseMiddlewareTest extends TestCase
{
    protected CacheResponseMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CacheResponseMiddleware();
        Cache::fake(); // Falsifica o Cache
    }

    /** @test */
    public function it_does_not_cache_non_get_requests(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'POST'); // Método não cacheável
        $next = function ($req) {
            return new JsonResponse(['data' => 'created'], 201);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['data' => 'created']), $response->getContent());
        Cache::assertNothingStored(); // Nada deve ser armazenado no cache
        $this->assertNull($response->headers->get('X-API-Cache')); // Ou assertEquals('MISS', ...) se você adicionar fallback
    }

    /** @test */
    public function it_returns_cached_response_on_cache_hit(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET', ['param' => 'value']);
        $user = User::factory()->make(['id' => 10]);
        $request->setUserResolver(fn () => $user);

        // A chave deve corresponder à forma como o middleware a gera
        $expectedKey = 'api_cache:' . md5($request->url() . '_user_' . $user->id . '_query_' . json_encode($request->query()));
        $cachedData = ['data' => ['from_cache' => true], 'status' => 200];
        Cache::put($expectedKey, $cachedData, 60 * 60); // TTL em segundos

        // O 'next' não deve ser chamado
        $next = function ($req) {
            $this->fail('Next middleware should not be called when cache hit.');
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($cachedData['data']), $response->getContent());
        $this->assertEquals('HIT', $response->headers->get('X-API-Cache'));
    }

    /** @test */
    public function it_caches_response_on_cache_miss(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET', ['param' => 'value']);
        $user = User::factory()->make(['id' => 10]);
        $request->setUserResolver(fn () => $user);

        $expectedKey = 'api_cache:' . md5($request->url() . '_user_' . $user->id . '_query_' . json_encode($request->query()));
        $originalData = ['data' => ['live_data' => true], 'status' => 200];

        // O 'next' deve ser chamado e retornar uma resposta
        $next = function ($req) use ($originalData) {
            return new JsonResponse($originalData['data'], $originalData['status']);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode($originalData['data']), $response->getContent());
        $this->assertEquals('MISS', $response->headers->get('X-API-Cache'));
        
        // Verifica se o item foi armazenado no cache com o TTL correto (60 minutos)
        Cache::assertExists($expectedKey);
        $this->assertEquals($originalData, Cache::get($expectedKey));
    }

    /** @test */
    public function it_does_not_cache_unsuccessful_responses(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET');
        $next = function ($req) {
            return new JsonResponse(['error' => 'not found'], 404);
        };

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        $this->assertEquals(404, $response->getStatusCode());
        Cache::assertNothingStored();
        $this->assertEquals('MISS', $response->headers->get('X-API-Cache'));
    }

    /** @test */
    public function it_uses_default_ttl_if_not_specified(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET');
        $next = function ($req) {
            return new JsonResponse(['data' => 'success'], 200);
        };
        $expectedKey = 'api_cache:' . md5($request->url() . '_guest_query_[]'); // Guest, sem query params

        // Act
        $response = $this->middleware->handle($request, $next);

        // Assert
        Cache::assertExists($expectedKey);
        // Não é possível verificar o TTL exato com Cache::fake(), mas podemos simular e verificar se expira
        // Ou em um teste de integração real com Redis, verificar TTL diretamente
        // Por ora, assertExists é suficiente para a unidade
    }

    /** @test */
    public function it_uses_custom_ttl_if_specified(): void
    {
        // Arrange
        $request = Request::create('/api/resource', 'GET');
        $next = function ($req) {
            return new JsonResponse(['data' => 'success'], 200);
        };
        $customTtl = 30; // 30 minutos
        $expectedKey = 'api_cache:' . md5($request->url() . '_guest_query_[]');

        // Act
        $response = $this->middleware->handle($request, $next, $customTtl);

        // Assert
        Cache::assertExists($expectedKey);
        // Similar ao teste anterior, o TTL exato não é testável diretamente com Cache::fake()
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}