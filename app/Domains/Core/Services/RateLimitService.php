<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Models\User;

class RateLimitService
{
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
    protected function getRateLimitKey(Request $request, ?User $user): string
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
    protected function getLimitForKey(string $key, ?User $user): int
    {
        // Limites diferenciados por tipo de usuário
        if ($user) {
            if ($user->is_premium) {
                return 300; // Limite maior para usuários premium
            }
            return 100; // Limite padrão para usuários autenticados
        }
        
        return 5; // Limite para usuários não autenticados
    }
    
    /**
     * Determina a janela de tempo em segundos para a chave
     *
     * @param string $key
     * @param User|null $user
     * @return int
     */
    protected function getTimeWindowForKey(string $key, ?User $user): int
    {
        // Janelas de tempo padrão (em segundos)
        return 60; // 1 minuto
    }
}
