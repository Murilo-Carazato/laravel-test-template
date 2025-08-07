<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

class ApiMetricsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Marca o tempo de início
        $startTime = microtime(true);

        // Processa a requisição
        $response = $next($request);

        // Calcula o tempo de execução
        $executionTime = microtime(true) - $startTime;

        // Coleta informações da rota
        $routeName = Route::currentRouteName() ?? 'unnamed';
        $method = $request->method();
        $path = $request->path();
        $statusCode = $response->getStatusCode();

        // Registra métricas
        $this->logMetrics([
            'route' => $routeName,
            'method' => $method,
            'path' => $path,
            'status_code' => $statusCode,
            'execution_time' => round($executionTime * 1000, 2), // Em milissegundos
            'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2), // Em MB
            'user_id' => $request->user() ? $request->user()->id : null,
        ]);

        return $response;
    }

    /**
     * Registra as métricas coletadas
     */
    private function logMetrics(array $metrics): void
    {
        Log::channel('api_metrics')->info('API Request', $metrics);

        // Aqui você poderia enviar essas métricas para um sistema como
        // Prometheus, Datadog, New Relic, etc.
    }

    /**
     * Verifica se uma solicitação excede os limites de taxa
     *
     * @param Request $request
     * @param User|null $user
     * @return array [exceedsLimit, remainingRequests, resetTime]
     */
    public function checkRateLimit(Request $request, ?User $user = null): array
    {
        $key = $this->getRateLimitKey($request, $user);
        $limit = $this->getLimitForKey($key, $user);
        $window = $this->getTimeWindowForKey($key, $user);

        // Chave Redis para o contador de requisições
        $redisKey = "rate_limit:{$key}";

        $current = Redis::get($redisKey) ?: 0;
        $current = (int) $current;
        $ttl = Redis::ttl($redisKey);

        // Se não há TTL ou é negativo, inicialize
        if ($ttl < 0) {
            Redis::set($redisKey, 1, 'EX', $window);
            $ttl = $window;
            $current = 1;
        } else {
            // Incrementa o contador
            $current = Redis::incr($redisKey);
        }

        // Calcula o tempo de reset
        $resetTime = now()->addSeconds($ttl);

        // Verifica se excede o limite
        $exceedsLimit = $current > $limit;

        // Calcula requisições restantes
        $remaining = max(0, $limit - $current);

        // Registra tentativas de excesso
        if ($exceedsLimit) {
            Log::channel('rate_limit')->warning('Rate limit exceeded', [
                'key' => $key,
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user_id' => $user?->id,
            ]);
        }

        return [$exceedsLimit, $remaining, $resetTime];
    }

    /**
     * Obtém uma chave única para limitação de taxa
     *
     * @param Request $request
     * @param User|null $user
     * @return string
     */
    public function getRateLimitKey(Request $request, ?User $user = null): string
    {
        // Se autenticado, use ID do usuário, caso contrário use IP
        if ($user) {
            $identifier = "user:{$user->id}";
        } else {
            $identifier = "ip:" . md5($request->ip());
        }

        // Adiciona endpoint para limites por rota
        $endpoint = "endpoint:" . md5($request->path());

        return "{$identifier}:{$endpoint}";
    }

    /**
     * Determina o limite apropriado para a chave
     *
     * @param string $key
     * @param User|null $user
     * @return int
     */
    public function getLimitForKey(string $key, ?User $user = null): int
    {
        // Limites diferenciados por tipo de usuário
        if ($user) {
            if ($user->is_premium) {
                return 300; // Limite maior para usuários premium
            }
            return 100; // Limite padrão para usuários autenticados
        }

        return 30; // Limite para usuários não autenticados
    }

    /**
     * Determina a janela de tempo em segundos para a chave
     *
     * @param string $key
     * @param User|null $user
     * @return int
     */
    public function getTimeWindowForKey(string $key, ?User $user = null): int
    {
        // Janelas de tempo padrão (em segundos)
        return 60; // 1 minuto
    }
}
