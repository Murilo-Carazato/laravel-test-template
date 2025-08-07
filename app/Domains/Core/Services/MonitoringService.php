<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class MonitoringService
{
    /**
     * Registra métricas de desempenho da aplicação
     *
     * @param string $metricName
     * @param float|int $value
     * @param array $tags
     * @return void
     */
    public function recordMetric(string $metricName, $value, array $tags = []): void
    {
        // Armazenamento temporário no Redis para agregar métricas
        $key = "metrics:{$metricName}:" . json_encode($tags);
        Redis::incrbyfloat($key, $value);
        Redis::expire($key, 3600); // Expiração em 1 hora
        
        // Opcional: Log para exportação posterior
        Log::channel('metrics')->info("METRIC", [
            'name' => $metricName,
            'value' => $value,
            'tags' => $tags,
            'timestamp' => microtime(true)
        ]);
    }
    
    /**
     * Registra um evento de negócio
     * 
     * @param string $eventName
     * @param array $payload
     * @return void
     */
    public function recordBusinessEvent(string $eventName, array $payload = []): void
    {
        Log::channel('business_events')->info($eventName, $payload);
        
        // Opcional: enviar para sistema externo de monitoramento
    }
}
