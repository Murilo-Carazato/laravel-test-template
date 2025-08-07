<?php

namespace App\Domains\Core\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BatchProcessorService
{
    /**
     * Processa uma coleção de itens em lotes
     *
     * @param Collection $items A coleção de itens a ser processada
     * @param callable $callback A função de callback que processa cada item
     * @param int $batchSize Tamanho do lote
     * @param bool $useTransaction Se deve usar transações de banco de dados
     * @return array Resultado com sucessos, falhas e estatísticas
     */
    public function process(
        Collection $items,
        callable $callback,
        int $batchSize = 100,
        bool $useTransaction = true
    ): array {
        $result = [
            'success' => [],
            'fail' => [],
            'statistics' => [
                'total' => $items->count(),
                'processed' => 0,
                'failed' => 0,
            ],
        ];

        $batches = $items->chunk($batchSize);

        foreach ($batches as $batch) {
            if ($useTransaction) {
                DB::transaction(function () use ($batch, $callback, &$result) {
                    $this->processBatch($batch, $callback, $result);
                });
            } else {
                $this->processBatch($batch, $callback, $result);
            }
        }

        return $result;
    }

    /**
     * Processa um lote de itens
     *
     * @param Collection $batch O lote de itens a ser processado
     * @param callable $callback A função de callback que processa cada item
     * @param array $result O array de resultado onde serão armazenados os sucessos e falhas
     */
    protected function processBatch(Collection $batch, callable $callback, array &$result)
    {
        foreach ($batch as $item) {
            try {
                $callback($item);
                $result['success'][] = $item;
            } catch (Throwable $e) {
                Log::error('Erro ao processar item: ' . $e->getMessage());
                $result['fail'][] = $item;
                $result['statistics']['failed']++;
            } finally {
                $result['statistics']['processed']++;
            }
        }
    }

    /**
     * Processa uma coleção de modelos em lote para inserção/atualização
     *
     * @param Collection $models Coleção de modelos a serem inseridos/atualizados
     * @param string $modelClass Nome da classe do modelo (ex: User::class)
     * @param array $uniqueKeys Campos a serem usados para identificar registros existentes
     * @param int $batchSize Tamanho do lote
     * @return array Resultados da operação
     */
    public function bulkUpsert(
        Collection $models,
        string $modelClass,
        array $uniqueKeys,
        int $batchSize = 100
    ): array {
        $result = [
            'success' => [],
            'fail' => [],
            'statistics' => [
                'total' => $models->count(),
                'upserted' => 0,
                'failed' => 0,
            ],
        ];

        $batches = $models->chunk($batchSize);

        foreach ($batches as $batch) {
            $upserted = $modelClass::upsert(
                $batch->toArray(),
                $uniqueKeys,
                array_keys($batch->first()->getAttributes())
            );

            $result['success'] = array_merge($result['success'], $upserted);
            $result['statistics']['upserted'] += count($upserted);
        }

        return $result;
    }
    
    /**
     * Executa uma operação em lote com retry automático para itens com falha
     *
     * @param Collection $items A coleção de itens a ser processada
     * @param callable $callback A função de callback que processa cada item
     * @param int $maxRetries Número máximo de tentativas
     * @param int $batchSize Tamanho do lote
     * @return array Resultado com sucessos, falhas e estatísticas
     */
    public function processWithRetry(
        Collection $items,
        callable $callback,
        int $maxRetries = 3,
        int $batchSize = 100
    ): array {
        $result = [
            'success' => [],
            'fail' => [],
            'statistics' => [
                'total' => $items->count(),
                'processed' => 0,
                'failed' => 0,
            ],
        ];

        $batches = $items->chunk($batchSize);

        foreach ($batches as $batch) {
            $this->processBatchWithRetry($batch, $callback, $maxRetries, $result);
        }

        return $result;
    }

    /**
     * Processa um lote de itens com tentativas automáticas em caso de falha
     *
     * @param Collection $batch O lote de itens a ser processado
     * @param callable $callback A função de callback que processa cada item
     * @param int $maxRetries Número máximo de tentativas
     * @param array $result O array de resultado onde serão armazenados os sucessos e falhas
     */
    protected function processBatchWithRetry(Collection $batch, callable $callback, int $maxRetries, array &$result)
    {
        foreach ($batch as $item) {
            $attempts = 0;
            $success = false;

            while (!$success && $attempts < $maxRetries) {
                try {
                    $callback($item);
                    $result['success'][] = $item;
                    $success = true;
                } catch (Throwable $e) {
                    $attempts++;
                    Log::warning('Tentativa ' . $attempts . ' falhou para o item: ' . $e->getMessage());

                    if ($attempts === $maxRetries) {
                        $result['fail'][] = $item;
                        $result['statistics']['failed']++;
                    }
                } finally {
                    $result['statistics']['processed']++;
                }
            }
        }
    }
}