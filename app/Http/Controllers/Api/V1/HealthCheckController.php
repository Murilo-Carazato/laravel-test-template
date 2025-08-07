<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Exception;

class HealthCheckController extends ApiController
{
    /**
     * Realiza uma verificação básica de saúde da API
     */
    public function basic(): JsonResponse
    {
        // Verificação simples para balanceadores de carga
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
    
    /**
     * Realiza uma verificação detalhada da saúde do sistema
     */
    public function detailed(): JsonResponse
    {
        $startTime = microtime(true);
        
        // Resultados da verificação
        $checks = [
            'api' => $this->checkApi(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
        ];
        
        // Verifica se todos os componentes estão saudáveis
        $isHealthy = !in_array(false, array_column($checks, 'healthy'));
        
        // Calcula a latência
        $latency = round((microtime(true) - $startTime) * 1000, 2);
        
        // Status HTTP baseado no resultado
        $statusCode = $isHealthy ? 200 : 503;
        
        return response()->json([
            'status' => $isHealthy ? 'ok' : 'unhealthy',
            'latency_ms' => $latency,
            'timestamp' => now()->toIso8601String(),
            'environment' => app()->environment(),
            'version' => config('app.version', '1.0.0'),
            'checks' => $checks,
        ], $statusCode);
    }
    
    /**
     * Verifica a API
     */
    private function checkApi(): array
    {
        return [
            'healthy' => true,
            'message' => 'API está respondendo corretamente',
        ];
    }
    
    /**
     * Verifica a conexão com o banco de dados
     */
    private function checkDatabase(): array
    {
        try {
            // Tenta executar uma consulta simples
            $start = microtime(true);
            DB::select('SELECT 1');
            $latency = round((microtime(true) - $start) * 1000, 2);
            
            return [
                'healthy' => true,
                'message' => 'Conexão com o banco de dados estabelecida com sucesso',
                'latency_ms' => $latency,
            ];
        } catch (Exception $e) {
            Log::error('Erro de conexão com o banco de dados: ' . $e->getMessage());
            
            return [
                'healthy' => false,
                'message' => 'Falha na conexão com o banco de dados',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verifica o sistema de cache
     */
    private function checkCache(): array
    {
        try {
            // Testa operações de cache
            $cacheKey = 'health_check_' . time();
            $cacheValue = 'health-check-' . time();
            
            $start = microtime(true);
            Cache::put($cacheKey, $cacheValue, 10);
            $retrievedValue = Cache::get($cacheKey);
            
            $latency = round((microtime(true) - $start) * 1000, 2);
            $healthy = $retrievedValue === $cacheValue;
            
            return [
                'healthy' => $healthy,
                'message' => $healthy 
                            ? 'Sistema de cache está funcionando corretamente' 
                            : 'Sistema de cache não está funcionando como esperado',
                'driver' => config('cache.default'),
                'latency_ms' => $latency,
            ];
        } catch (Exception $e) {
            Log::error('Erro no sistema de cache: ' . $e->getMessage());
            
            return [
                'healthy' => false,
                'message' => 'Falha no sistema de cache',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Verifica o sistema de armazenamento
     */
    private function checkStorage(): array
    {
        try {
            // Testa operações de armazenamento
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'Health check test at ' . now()->toIso8601String();
            
            $start = microtime(true);
            Storage::put($testFile, $testContent);
            $exists = Storage::exists($testFile);
            $retrieved = $exists ? Storage::get($testFile) : null;
            
            if ($exists) {
                Storage::delete($testFile);
            }
            
            $latency = round((microtime(true) - $start) * 1000, 2);
            $healthy = $exists && $retrieved === $testContent;
            
            return [
                'healthy' => $healthy,
                'message' => $healthy 
                            ? 'Sistema de armazenamento está funcionando corretamente' 
                            : 'Sistema de armazenamento não está funcionando como esperado',
                'disk' => config('filesystems.default'),
                'latency_ms' => $latency,
            ];
        } catch (Exception $e) {
            Log::error('Erro no sistema de armazenamento: ' . $e->getMessage());
            
            return [
                'healthy' => false,
                'message' => 'Falha no sistema de armazenamento',
                'disk' => config('filesystems.default'),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Endpoint para diagnósticos avançados (protegido)
     */
    public function diagnostics(): JsonResponse
    {
        // Verificar permissões antes de prosseguir
        if (!$this->canAccessDiagnostics()) {
            return response()->json([
                'message' => 'Acesso não autorizado para diagnósticos'
            ], 403);
        }
        
        // Coleta informações do sistema
        $diagnostics = [
            'system' => [
                'php_version' => phpversion(),
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'memory_usage' => $this->formatBytes(memory_get_usage(true)),
                'max_memory' => ini_get('memory_limit'),
                'timezone' => config('app.timezone'),
                'time' => now()->toDateTimeString(),
            ],
            'laravel' => [
                'version' => app()->version(),
                'environment' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'maintenance_mode' => app()->isDownForMaintenance(),
                'config_cached' => app()->configurationIsCached(),
                'routes_cached' => app()->routesAreCached(),
            ],
            'database' => $this->getDatabaseDiagnostics(),
            'cache' => $this->getCacheDiagnostics(),
            'queue' => $this->getQueueDiagnostics(),
        ];
        
        return response()->json($diagnostics);
    }
    
    /**
     * Verifica se o usuário atual pode acessar diagnósticos
     */
    private function canAccessDiagnostics(): bool
    {
        // Em um ambiente real, você verificaria permissões específicas
        // Por exemplo: return auth()->user()->hasRole('admin');
        return app()->environment() !== 'production' || 
               request()->header('X-Admin-Token') === config('app.admin_token');
    }
    
    /**
     * Coleta diagnósticos do banco de dados
     */
    private function getDatabaseDiagnostics(): array
    {
        try {
            // Estatísticas básicas do banco
            $stats = DB::select("SELECT version() as version");
            
            return [
                'connection' => config('database.default'),
                'driver' => config('database.connections.' . config('database.default') . '.driver'),
                'version' => $stats[0]->version ?? 'Unknown',
                'queries_slow' => $this->getSlowQueriesCount(), // Implementação dependente do driver
            ];
        } catch (Exception $e) {
            return [
                'connection' => config('database.default'),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Coleta diagnósticos do cache
     */
    private function getCacheDiagnostics(): array
    {
        try {
            return [
                'driver' => config('cache.default'),
                'prefix' => config('cache.prefix'),
                'store' => get_class(Cache::store()),
            ];
        } catch (Exception $e) {
            return [
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Coleta diagnósticos das filas
     */
    private function getQueueDiagnostics(): array
    {
        try {
            // Estatísticas básicas das filas
            return [
                'connection' => config('queue.default'),
                'driver' => config('queue.connections.' . config('queue.default') . '.driver'),
            ];
        } catch (Exception $e) {
            return [
                'connection' => config('queue.default'),
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Formata bytes para exibição legível
     */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Obtém contagem de queries lentas (implementação simplificada)
     */
    private function getSlowQueriesCount(): int
    {
        // Em um ambiente real, isso dependeria do driver do banco de dados
        // Por exemplo, consultas ao MySQL performance_schema
        return rand(0, 5); // Simulação
    }
}
