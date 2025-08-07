<?php

namespace App\Domains\Core\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;
use Throwable;

class ExportService
{
    /**
     * Número de registros a serem processados por vez
     *
     * @var int
     */
    protected $chunkSize = 1000;

    /**
     * Diretório para armazenar arquivos exportados
     *
     * @var string
     */
    protected $exportDirectory = 'exports';

    /**
     * Exporta uma consulta para CSV
     *
     * @param Builder $query Consulta a ser exportada
     * @param array $columns Colunas a serem exportadas ['column' => 'Label']
     * @param string $filename Nome do arquivo (sem extensão)
     * @param bool $includeHeaders Se deve incluir cabeçalhos
     * @return string Caminho do arquivo exportado
     */
    public function exportToCsv(Builder $query, array $columns, string $filename, bool $includeHeaders = true): string
    {
        // Gerar nome de arquivo único
        $filename = $this->generateFilename($filename, 'csv');
        $path = $this->exportDirectory . '/' . $filename;
        
        // Criar o arquivo CSV
        $csv = Writer::createFromStream(fopen('php://temp', 'r+'));
        $csv->setDelimiter(',');
        $csv->setEnclosure('"');
        $csv->setEscape('\\');
        
        // Adicionar cabeçalhos se necessário
        if ($includeHeaders) {
            $csv->insertOne(array_values($columns));
        }
        
        // Processar os dados em chunks para evitar problemas de memória
        $query->chunk($this->chunkSize, function ($records) use ($csv, $columns) {
            foreach ($records as $record) {
                $row = [];
                foreach (array_keys($columns) as $column) {
                    $row[] = data_get($record, $column, '');
                }
                $csv->insertOne($row);
            }
        });
        
        // Armazenar o arquivo no disco
        Storage::put($path, $csv->getContent());
        
        return $path;
    }
    
    /**
     * Exporta uma consulta para Excel
     *
     * @param Builder $query Consulta a ser exportada
     * @param array $columns Colunas a serem exportadas ['column' => 'Label']
     * @param string $filename Nome do arquivo (sem extensão)
     * @return string Caminho do arquivo exportado
     */
    public function exportToExcel(Builder $query, array $columns, string $filename): string
    {
        // Gerar nome de arquivo único
        $filename = $this->generateFilename($filename, 'xlsx');
        $path = $this->exportDirectory . '/' . $filename;
        
        // Criar exportador Excel
        $export = new class($query, $columns, $this->chunkSize) {
            protected $query;
            protected $columns;
            protected $chunkSize;
            
            public function __construct($query, $columns, $chunkSize)
            {
                $this->query = $query;
                $this->columns = $columns;
                $this->chunkSize = $chunkSize;
            }
            
            public function collection()
            {
                $results = collect();
                
                $this->query->chunk($this->chunkSize, function ($records) use (&$results) {
                    foreach ($records as $record) {
                        $row = [];
                        foreach (array_keys($this->columns) as $column) {
                            $row[$column] = data_get($record, $column, '');
                        }
                        $results->push($row);
                    }
                });
                
                return $results;
            }
            
            public function headings(): array
            {
                return array_values($this->columns);
            }
        };
        
        // Armazenar em disco
        (new \Maatwebsite\Excel\Excel)
            ->store($export, $path, 's3', \Maatwebsite\Excel\Excel::XLSX);
        
        return $path;
    }
    
    /**
     * Retorna uma resposta para download do arquivo exportado
     *
     * @param string $path Caminho do arquivo exportado
     * @param string $filename Nome para download
     * @return StreamedResponse
     */
    public function downloadExport(string $path, string $filename): StreamedResponse
    {
        return Storage::download($path, $filename);
    }
    
    /**
     * Cria uma exportação em segundo plano
     *
     * @param Builder $query Consulta a ser exportada
     * @param array $columns Colunas a serem exportadas
     * @param string $type Tipo de exportação (csv, excel)
     * @param string $filename Nome base do arquivo
     * @return string ID da tarefa de exportação
     */
    public function createBackgroundExport(Builder $query, array $columns, string $type, string $filename): string
    {
        $exportId = (string) Str::uuid();
        
        // Armazenar detalhes da exportação
        $exportData = [
            'id' => $exportId,
            'query' => serialize($query->getQuery()),
            'model' => get_class($query->getModel()),
            'columns' => $columns,
            'type' => $type,
            'filename' => $filename,
            'status' => 'pending',
            'created_at' => now()->toDateTimeString(),
        ];
        
        Cache::put('export:' . $exportId, $exportData, now()->addDays(1));
        
        // Despachar job para processamento em segundo plano
        \App\Jobs\ProcessExport::dispatch($exportId);
        
        return $exportId;
    }
    
    /**
     * Gera um nome de arquivo único
     *
     * @param string $basename Nome base do arquivo
     * @param string $extension Extensão do arquivo
     * @return string
     */
    protected function generateFilename(string $basename, string $extension): string
    {
        $timestamp = now()->format('Y-m-d_His');
        $random = Str::random(8);
        
        return Str::slug($basename) . "_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Define o tamanho do chunk para processamento
     *
     * @param int $size
     * @return $this
     */
    public function setChunkSize(int $size): self
    {
        $this->chunkSize = $size;
        return $this;
    }
}
