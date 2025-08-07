<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Queue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Exception;

class QueueManagerService
{
    // Constantes de filas por prioridade
    const QUEUE_HIGH = 'high';
    const QUEUE_DEFAULT = 'default';
    const QUEUE_LOW = 'low';
    
    // Conexões suportadas
    const CONNECTION_SYNC = 'sync';
    const CONNECTION_DATABASE = 'database';
    const CONNECTION_REDIS = 'redis';
    
    /**
     * Configura a conexão e fila padrão
     */
    protected string $defaultConnection;
    protected string $defaultQueue;
    
    /**
     * Métricas de jobs
     */
    protected bool $collectMetrics = true;
    
    /**
     * Construtor
     */
    public function __construct(string $connection = null, string $queue = null)
    {
        $this->defaultConnection = $connection ?? config('queue.default');
        $this->defaultQueue = $queue ?? self::QUEUE_DEFAULT;
    }

    /**
     * Dispara um job com configurações específicas
     */
    public function dispatch(
        ShouldQueue $job,
        string $queue = null,
        string $connection = null,
        ?Carbon $delay = null
    ): mixed {
        $connection = $connection ?? $this->defaultConnection;
        $queue = $queue ?? $this->defaultQueue;
        
        // Registre o job antes do envio
        if ($this->collectMetrics) {
            $this->trackJobQueued($job, $queue, $connection);
        }
        
        $jobInstance = $delay 
            ? dispatch($job)->onConnection($connection)->onQueue($queue)->delay($delay) 
            : dispatch($job)->onConnection($connection)->onQueue($queue);
            
        return $jobInstance;
    }
    
    /**
     * Envia um job para a fila de alta prioridade
     */
    public function dispatchHigh(ShouldQueue $job, string $connection = null, ?Carbon $delay = null): mixed
    {
        return $this->dispatch($job, self::QUEUE_HIGH, $connection, $delay);
    }
    
    /**
     * Envia um job para a fila de baixa prioridade
     */
    public function dispatchLow(ShouldQueue $job, string $connection = null, ?Carbon $delay = null): mixed
    {
        return $this->dispatch($job, self::QUEUE_LOW, $connection, $delay);
    }
    
    /**
     * Envia um job para ser executado de forma síncrona
     */
    public function dispatchSync(ShouldQueue $job): mixed
    {
        return $this->dispatch($job, self::QUEUE_DEFAULT, self::CONNECTION_SYNC);
    }
    
    /**
     * Envia um job para ser executado após um delay específico
     */
    public function dispatchAfter(ShouldQueue $job, int $seconds, string $queue = null, string $connection = null): mixed
    {
        $delay = now()->addSeconds($seconds);
        return $this->dispatch($job, $queue, $connection, $delay);
    }
    
    /**
     * Registra métricas do job que está sendo enviado
     */
    protected function trackJobQueued(ShouldQueue $job, string $queue, string $connection): void
    {
        try {
            $jobName = get_class($job);
            $date = now()->format('Y-m-d');
            
            // Incrementa contagem diária por tipo de job e fila
            Cache::increment("jobs:{$date}:{$jobName}:{$queue}:queued");
            Cache::increment("jobs:{$date}:total:queued");
            
            // Registra em log
            Log::debug("Job [$jobName] enviado para fila [$queue] na conexão [$connection]");
        } catch (Exception $e) {
            Log::error("Erro ao rastrear job: " . $e->getMessage());
        }
    }
    
    /**
     * Configura a conexão padrão
     */
    public function setDefaultConnection(string $connection): self
    {
        $this->defaultConnection = $connection;
        return $this;
    }
    
    /**
     * Configura a fila padrão
     */
    public function setDefaultQueue(string $queue): self
    {
        $this->defaultQueue = $queue;
        return $this;
    }
    
    /**
     * Ativa ou desativa coleta de métricas
     */
    public function collectMetrics(bool $collect = true): self
    {
        $this->collectMetrics = $collect;
        return $this;
    }
    
    /**
     * Obtém estatísticas da fila
     */
    public function getQueueStatistics(string $queue = null, string $date = null): array
    {
        $queue = $queue ?? $this->defaultQueue;
        $date = $date ?? now()->format('Y-m-d');
        
        return [
            'queued' => (int) Cache::get("jobs:{$date}:total:queued", 0),
            'processed' => (int) Cache::get("jobs:{$date}:total:processed", 0),
            'failed' => (int) Cache::get("jobs:{$date}:total:failed", 0),
        ];
    }
    
    /**
     * Verifica se a fila está sobrecarregada (atingiu um limite)
     */
    public function isQueueOverloaded(string $queue = null, int $threshold = 1000): bool
    {
        $queue = $queue ?? $this->defaultQueue;
        $stats = $this->getQueueStatistics($queue);
        
        $pending = $stats['queued'] - ($stats['processed'] + $stats['failed']);
        return $pending >= $threshold;
    }
}
