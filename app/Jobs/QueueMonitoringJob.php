<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class QueueMonitoringJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Coletar métricas sobre filas
        $queueMetrics = [
            'timestamp' => now()->toIso8601String(),
            'queues' => []
        ];

        // Lista de filas para monitorar
        $queues = ['default', 'notifications', 'processing', 'long-running'];

        foreach ($queues as $queue) {
            $queueSize = Redis::connection()->llen("queues:{$queue}");
            $queueMetrics['queues'][$queue] = [
                'size' => $queueSize,
                'status' => $this->getQueueStatus($queueSize)
            ];
        }

        // Registrar métricas em log
        Log::channel('queue_monitoring')->info('Queue metrics collected', $queueMetrics);

        // Em uma implementação real, você poderia enviar para um serviço de monitoramento
        // como Prometheus, Datadog, New Relic, etc.
        
        // Agendar novamente para execução em 5 minutos
        self::dispatch()->delay(now()->addMinutes(5));
    }

    /**
     * Determina o status da fila com base no tamanho
     */
    private function getQueueStatus(int $size): string
    {
        if ($size === 0) {
            return 'healthy';
        } elseif ($size < 100) {
            return 'normal';
        } elseif ($size < 500) {
            return 'busy';
        } else {
            return 'overloaded';
        }
    }

    /**
     * Manipular falha do job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Queue monitoring job failed', [
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
