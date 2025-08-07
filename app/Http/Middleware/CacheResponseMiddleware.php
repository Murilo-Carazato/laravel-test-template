<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $ttl  Tempo de cache em minutos
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $ttl = 60)
    {
        // Não aplica cache para métodos que modificam dados
        if (!$request->isMethodCacheable()) {
            return $next($request);
        }
        
        // Cria uma chave única baseada na URL e no usuário (se autenticado)
        $key = 'api_cache:' . md5($request->url() . 
               ($request->user() ? '_user_' . $request->user()->id : '_guest') .
               '_query_' . json_encode($request->query()));
        
        // Verifica se a resposta está em cache
        if (Cache::has($key)) {
            $cachedResponse = Cache::get($key);
            // Adiciona header indicando que veio do cache
            return response()->json(
                $cachedResponse['data'],
                $cachedResponse['status']
            )->header('X-API-Cache', 'HIT');
        }
        
        // Processa a requisição
        $response = $next($request);
        
        // Armazena a resposta em cache se for bem-sucedida
        if ($response->isSuccessful()) {
            $responseData = [
                'data' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode()
            ];
            Cache::put($key, $responseData, $ttl * 60);
        }
        
        // Adiciona header indicando que não veio do cache
        $response->headers->set('X-API-Cache', 'MISS');
        
        return $response;
    }
}