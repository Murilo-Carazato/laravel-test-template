<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class Handler extends ExceptionHandler
{
    protected $levels = [];

    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
        TooManyRequestsHttpException::class,
    ];

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'token',
        'api_token',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if ($this->shouldReport($e)) {
                Log::error('Exception occurred', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'user_id' => Auth::user()?->id,
                    'ip' => request()->ip() ?? 'unknown',
                    'user_agent' => request()->userAgent() ?? 'unknown',
                ]);
            }
        });
    }


    public function render($request, Throwable $exception)
    {
        // TEMPORÁRIO - force sempre API para rotas /api/*
        $path = $request->getPathInfo();
        if (Str::startsWith($path, '/api')) {
            Log::info('=== FORCED API HANDLING ===');
            return $this->handleApiException($request, $exception);
        }

        return parent::render($request, $exception);
    }

    private function isApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();

        // Debug - force para testar
        $isApi = $request->expectsJson() ||
            $request->is('api/*') ||
            $request->is('api/v*/*') ||
            Str::startsWith($path, '/api/v1') ||
            Str::startsWith($path, '/api') ||
            $request->routeIs('api.*');

        Log::info('=== isApiRequest DEBUG ===', [
            'path' => $path,
            'expects_json' => $request->expectsJson(),
            'is_api_wildcard' => $request->is('api/*'),
            'is_api_v_wildcard' => $request->is('api/v*/*'),
            'starts_with_api_v1' => Str::startsWith($path, '/api/v1'),
            'starts_with_api' => Str::startsWith($path, '/api'),
            'route_is_api' => $request->routeIs('api.*'),
            'final_result' => $isApi
        ]);

        return $isApi;
    }

    private function handleApiException(Request $request, Throwable $exception): JsonResponse
    {
        $statusCode = $this->getStatusCodeFromException($exception);
        $message = $this->getMessageFromException($exception);
        $errorCode = $this->getErrorCodeFromException($exception);

        // Dados específicos para certos tipos de erro
        $errors = null;
        if ($exception instanceof ValidationException) {
            $errors = $exception->validator->errors()->toArray();
        }

        $response = [
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'data' => null,
            'timestamp' => now()->toISOString(),
            'request_id' => (string) Str::uuid(),
        ];

        // Adiciona erros de validação se existirem
        if ($errors) {
            $response['errors'] = $errors;
        }

        // Adiciona informações de debug se estiver em desenvolvimento
        if ($this->shouldIncludeDebugInfo()) {
            $response['debug'] = [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];
        }

        $jsonResponse = response()->json($response, $statusCode);

        // Adiciona headers específicos do rate limiting
        if ($exception instanceof TooManyRequestsHttpException) {
            $retryAfter = $exception->getHeaders()['Retry-After'] ?? 60;
            $jsonResponse->headers->add([
                'Retry-After' => $retryAfter,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        return $jsonResponse;
    }

    private function getStatusCodeFromException(Throwable $exception): int
    {
        return match (true) {
            $exception instanceof ValidationException => 422,
            $exception instanceof AuthenticationException => 401,
            $exception instanceof AuthorizationException => 403,
            $exception instanceof ModelNotFoundException,
            $exception instanceof NotFoundHttpException => 404,
            $exception instanceof MethodNotAllowedHttpException => 405,
            $exception instanceof TooManyRequestsHttpException => 429,
            $exception instanceof HttpException => $exception->getStatusCode(),
            $exception instanceof QueryException && $exception->errorInfo[1] === 1062 => 409,
            $exception instanceof QueryException && $exception->errorInfo[1] === 1451 => 409,
            default => 500
        };
    }

    private function getMessageFromException(Throwable $exception): string
    {
        return match (true) {
            $exception instanceof ValidationException => 'Os dados fornecidos são inválidos.',
            $exception instanceof AuthenticationException => 'Credenciais inválidas ou token expirado.',
            $exception instanceof AuthorizationException => 'Você não tem permissão para realizar esta ação.',
            $exception instanceof ModelNotFoundException => $this->getModelNotFoundMessage($exception),
            $exception instanceof NotFoundHttpException => 'O recurso solicitado não foi encontrado.',
            $exception instanceof MethodNotAllowedHttpException => 'Método HTTP não permitido para esta rota.',
            $exception instanceof TooManyRequestsHttpException => 'Muitas tentativas. Tente novamente mais tarde.',
            $exception instanceof QueryException => $this->getDatabaseErrorMessage($exception),
            $exception instanceof HttpException => $exception->getMessage() ?: 'Erro na requisição.',
            default => $this->shouldIncludeDebugInfo()
                ? $exception->getMessage()
                : 'Ocorreu um erro interno no servidor.'
        };
    }

    private function getModelNotFoundMessage(ModelNotFoundException $exception): string
    {
        $model = $exception->getModel();
        $modelName = class_basename($model);

        $translations = [
            'User' => 'usuário',
            'Profile' => 'perfil',
            'Feature' => 'funcionalidade',
            'Audit' => 'auditoria',
        ];

        $translatedName = $translations[$modelName] ?? strtolower($modelName);

        return "O(a) {$translatedName} solicitado(a) não foi encontrado(a).";
    }

    private function getDatabaseErrorMessage(QueryException $exception): string
    {
        $errorCode = $exception->errorInfo[1] ?? 0;

        return match ($errorCode) {
            1062 => 'Este registro já existe. Verifique os dados únicos como email.',
            1451 => 'Não é possível excluir este registro pois ele está sendo usado por outros dados.',
            1452 => 'Erro de referência. Verifique se todos os dados relacionados existem.',
            default => $this->shouldIncludeDebugInfo()
                ? "Erro de banco de dados: {$exception->getMessage()}"
                : 'Erro de banco de dados. Verifique os dados enviados.'
        };
    }

    private function getErrorsFromException(Throwable $exception): ?array
    {
        if ($exception instanceof ValidationException) {
            return $exception->errors();
        }

        return null;
    }

    private function getErrorCodeFromException(Throwable $exception): ?string
    {
        return match (true) {
            $exception instanceof ValidationException => 'VALIDATION_ERROR',
            $exception instanceof AuthenticationException => 'AUTHENTICATION_ERROR',
            $exception instanceof AuthorizationException => 'AUTHORIZATION_ERROR',
            $exception instanceof ModelNotFoundException => 'RESOURCE_NOT_FOUND',
            $exception instanceof TooManyRequestsHttpException => 'RATE_LIMIT_EXCEEDED', // Adicione esta linha
            $exception instanceof QueryException => 'DATABASE_ERROR',
            default => null
        };
    }

    private function getTraceFromException(Throwable $exception): ?array
    {
        if (!$this->shouldIncludeDebugInfo()) {
            return null;
        }

        return collect($exception->getTrace())
            ->filter(fn($trace) => !Str::contains($trace['file'] ?? '', ['/vendor/', '/bootstrap/']))
            ->take(10)
            ->map(fn($trace) => [
                'file' => $trace['file'] ?? 'unknown',
                'line' => $trace['line'] ?? 'unknown',
                'function' => $trace['function'] ?? 'unknown',
                'class' => $trace['class'] ?? null,
            ])
            ->values()
            ->toArray();
    }

    private function shouldIncludeDebugInfo(): bool
    {
        return config('app.debug') && !app()->environment('production');
    }

    private function generateRequestId(): string
    {
        return request()->header('X-Request-ID') ?? Str::uuid()->toString();
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Token de autenticação não fornecido ou inválido.',
                'error_code' => 'UNAUTHENTICATED',
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        return redirect()->guest($exception->redirectTo($request) ?? '/login');
    }
}
