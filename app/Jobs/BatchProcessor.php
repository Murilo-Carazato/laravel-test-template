<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchProcessor implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $items;
    protected $processCallback;
    protected $batchId;
    protected $options;

    /**
     * Create a new job instance.
     *
     * @param array $items Os itens a serem processados
     * @param callable $processCallback A funÃ§Ã£o que processa cada item
     * @param string $batchId Identificador Ãºnico do lote
     * @param array $options OpÃ§Ãµes adicionais de configuraÃ§Ã£o
     */
    public function __construct(array $items, callable $processCallback, string $batchId, array $options = [])
    {
        $this->items = $items;
        $this->processCallback = $processCallback;
        $this->batchId = $batchId;
        $this->options = array_merge([
            'chunk_size' => 100,
            'retry_attempts' => 3,
            'priority' => 'medium',
        ], $options);
        
        // Configurar as tentativas e prioridade da fila
        $this->tries = $this->options['retry_attempts'];
        $this->queue = $this->options['priority'];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Log::info("Iniciando processamento do lote {$this->batchId} com " . count($this->items) . " itens");
        
        $processed = 0;
        $failed = 0;
        
        // Processamento em chunks para gerenciar memÃ³ria
        foreach (array_chunk($this->items, $this->options['chunk_size']) as $chunk) {
            foreach ($chunk as $item) {
                try {
                    call_user_func($this->processCallback, $item);
                    $processed++;
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Erro no processamento do lote {$this->batchId}: " . $e->getMessage(), [
                        'item' => $item,
                        'exception' => get_class($e),
                    ]);
                }
            }
        }
        
        Log::info("Finalizado processamento do lote {$this->batchId}", [
            'processed' => $processed,
            'failed' => $failed,
            'total' => count($this->items),
        ]);
    }
    
    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Falha crÃ­tica no processamento do lote {$this->batchId}: " . $exception->getMessage());
        
        // Aqui vocÃª pode implementar notificaÃ§Ãµes de falha, por exemplo:
        // NotificationService::sendAlert("Falha no processamento do lote {$this->batchId}");
    }
}