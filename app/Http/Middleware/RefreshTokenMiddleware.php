<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

class RefreshTokenMiddleware
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
        // Processa a requisição primeiro
        $response = $next($request);
        
        // Verifica se o usuário está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            $token = $request->bearerToken();
            
            // Verifica se o token atual está próximo de expirar (menos de 30 min)
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $this->tokenNeedsRefresh($accessToken)) {
                // Cria um novo token
                $newToken = $user->createToken('api-refresh')->plainTextToken;
                
                // Adiciona o novo token no header da resposta
                $response->headers->set('X-New-Token', $newToken);
            }
        }
        
        return $response;
    }
    
    /**
     * Verifica se o token precisa ser atualizado
     */
    private function tokenNeedsRefresh($token): bool
    {
        // Verifica se o token expira em menos de 30 minutos
        // Ajuste conforme suas necessidades
        $expiresAt = $token->created_at->addMinutes(config('sanctum.expiration', 60));
        $refreshThreshold = now()->addMinutes(30);
        
        return $expiresAt->lt($refreshThreshold);
    }
}