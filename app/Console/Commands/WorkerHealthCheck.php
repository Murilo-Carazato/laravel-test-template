<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class WorkerHealthCheck extends Command
{
    /**
     * O nome e a assinatura do comando do console.
     *
     * @var string
     */
    protected $signature = 'queue:health-check 
                            {--connection=redis : A conexão de fila a ser verificada}
                            {--threshold=300 : O tempo limite em segundos antes de considerar um worker como inativo}
                            {--alert : Enviar alertas sobre workers inativos}';

    /**
     * A descrição do comando do console.
     *
     * @var string
     */
    protected $description = 'Verifica a saúde dos queue workers e reporta workers inativos';

    /**
     * Executa o comando do console.
     */
    public function handle()
    {
        $connection = $this->option('connection');
        $thresholdSeconds = (int) $this->option('threshold');
        $shouldAlert = $this->option('alert');
        
        $this->info("Verificando a saúde dos workers na conexão [{$connection}]...");
        
        // Verifica cada tipo de fila
        $queues = ['high', 'default', 'low'];
        $inactive = [];
        $active = [];
        
        foreach ($queues as $queue) {
            $this->checkQueueWorkers($connection, $queue, $thresholdSeconds, $inactive, $active);
        }
        
        // Exibe resultados
        $this->info("\nResumo da saúde dos workers:");
        $this->info("- Workers ativos: " . count($active));
        $this->warn("- Workers inativos: " . count($inactive));
        
        if (count($inactive) > 0) {
            $this->error("\nWorkers inativos:");
            foreach ($inactive as $worker) {
                $this->error("- {$worker['id']}: Última atividade há {$worker['inactive_for']} segundos na fila {$worker['queue']}");
            }
            
            if ($shouldAlert) {
                $this->sendAlert($inactive);
            }
        }
        
        return Command::SUCCESS;
    }
    
    /**
     * Verifica os workers de uma fila específica
     */
    protected function checkQueueWorkers(
        string $connection, 
        string $queue, 
        int $thresholdSeconds,
        array &$inactive, 
        array &$active
    ): void {
        $this->info("\nVerificando workers da fila [{$queue}]...");
        
        try {
            // Em um ambiente real, você consultaria a lista de workers ativos
            // Aqui estamos simulando a verificação de heartbeats de workers
            $workers = $this->getWorkerHeartbeats($connection, $queue);
            
            if (empty($workers)) {
                $this->warn("Nenhum worker encontrado para a fila [{$queue}]");
                return;
            }
            
            foreach ($workers as $workerId => $lastBeat) {
                $lastActive = Carbon::createFromTimestamp($lastBeat);
                $inactiveSeconds = now()->diffInSeconds($lastActive);
                
                if ($inactiveSeconds > $thresholdSeconds) {
                    $inactive[] = [
                        'id' => $workerId,
                        'queue' => $queue,
                        'last_active' => $lastActive->toDateTimeString(),
                        'inactive_for' => $inactiveSeconds
                    ];
                    
                    $this->warn("- Worker {$workerId}: Inativo há {$inactiveSeconds} segundos");
                } else {
                    $active[] = [
                        'id' => $workerId,
                        'queue' => $queue,
                        'last_active' => $lastActive->toDateTimeString()
                    ];
                    
                    $this->info("- Worker {$workerId}: Ativo (última batida há {$inactiveSeconds} segundos)");
                }
            }
        } catch (\Exception $e) {
            $this->error("Erro ao verificar workers: " . $e->getMessage());
            Log::error("WorkerHealthCheck falhou: " . $e->getMessage());
        }
    }
    
    /**
     * Simula a obtenção de heartbeats dos workers
     */
    protected function getWorkerHeartbeats(string $connection, string $queue): array
    {
        // Em uma implementação real, você consultaria o Redis, banco de dados etc.
        // Aqui estamos simulando alguns workers com timestamps diferentes
        
        $workers = [];
        
        // Simula 2 workers por fila
        if ($queue === 'high') {
            $workers['worker_high_1'] = time() - rand(0, 200); // Worker ativo
            $workers['worker_high_2'] = time() - rand(350, 600); // Potencialmente inativo
        } elseif ($queue === 'default') {
            $workers['worker_default_1'] = time() - rand(0, 100); // Worker ativo
            $workers['worker_default_2'] = time() - rand(0, 150); // Worker ativo
        } else {
            $workers['worker_low_1'] = time() - rand(250, 400); // Potencialmente inativo
        }
        
        return $workers;
    }
    
    /**
     * Envia alerta sobre workers inativos
     */
    protected function sendAlert(array $inactiveWorkers): void
    {
        // Em uma implementação real, você enviaria alertas via email, Slack, etc.
        $this->info("\nEnviando alertas para " . count($inactiveWorkers) . " workers inativos...");
        
        // Registra os workers inativos
        Log::warning("Workers inativos detectados:", $inactiveWorkers);
    }
}
