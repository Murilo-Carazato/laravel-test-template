<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domains\Core\Services\RateLimitService;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class ApiRateLimitMiddleware
{
    protected RateLimitService $rateLimitService;
    
    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        [$exceedsLimit, $remaining, $resetTime] = $this->rateLimitService->checkRateLimit($request, $request->user());
        
        if ($exceedsLimit) {
            // Lança exception que será capturada pelo Handler
            throw new TooManyRequestsHttpException(
                retryAfter: $resetTime->diffInSeconds(now()),
                message: 'Limite de requisições excedido. Aguarde antes de tentar novamente.'
            );
        }
        
        $response = $next($request);
        
        // Adiciona headers de rate limit
        $response->headers->add([
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetTime->timestamp,
        ]);
        
        return $response;
    }
}