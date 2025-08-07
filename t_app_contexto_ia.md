# Contexto do Projeto: app

*Gerado em: 2025-05-28T15:07:10.480Z*
*Pasta Raiz: `C:\Users\Murilo Carazato\Documents\Laravel Projects\HUB\teste-template\app`*

## Conteúdo dos Arquivos

### Arquivo: `Console\Commands\SetupHorizonWorkers.php`


#### Conteúdo

```php
<?php

namespace App\Console\Commands;


class SetupHorizonWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup-horizon-workers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configura e inicia os workers do Laravel Horizon';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Configurando Laravel Horizon...');
        
        // Publicar configuração se não existir
        if (!file_exists(config_path('horizon.php'))) {
            $this->info('Publicando arquivos de configuração do Horizon...');
            Artisan::call('vendor:publish', [
                '--provider' => 'Laravel\Horizon\HorizonServiceProvider',
            ]);
            $this->info(Artisan::output());
        }
        
        // Reiniciar workers
        $this->info('Reiniciando workers do Horizon...');
        Artisan::call('horizon:terminate');
        $this->info('Workers terminados. Iniciando novos workers...');
        
        // Em ambiente de produção, seria iniciado como daemon
        if (app()->environment('production')) {
            $this->info('Ambiente de produção detectado. Use o supervisor para gerenciar o Horizon.');
            $this->info('Exemplo de configuração do supervisor:');
            $this->line('[program:horizon]');
            $this->line('process_name=%(program_name)s');
            $this->line('command=php /path/to/artisan horizon');
            $this->line('autostart=true');
            $this->line('autorestart=true');
            $this->line('user=www-data');
            $this->line('redirect_stderr=true');
            $this->line('stdout_logfile=/path/to/horizon.log');
            $this->line('stopwaitsecs=3600');
        } else {
            // Em desenvolvimento, apenas mostra o comando
            $this->info('Para iniciar o Horizon em desenvolvimento:');
            $this->line('php artisan horizon');
        }
        
        $this->info('Configuração concluída!');
    }
}
```

### Arquivo: `Domains\Auth\Commands\LoginCommand.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Auth\Commands;


class LoginCommand
{
    protected $authService;
    
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function handle(string $email, string $password): array
    {
        return $this->authService->authenticate($email, $password);
    }
}
```

### Arquivo: `Domains\Auth\Queries\GetCurrentUserQuery.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Auth\Queries;


class GetCurrentUserQuery
{
    protected $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle()
    {
        $user = Auth::user();
        return $user ? $this->userRepository->find($user->id) : null;
    }
}
```

### Arquivo: `Domains\Auth\Services\AuthService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Auth\Services;


class AuthService implements AuthServiceInterface
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Cria um perfil vazio para o usuário
        $user->profile()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => UserResource::make($user),
            'token' => $token
        ];
    }

    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        
        // Revoga tokens anteriores e cria um novo
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => UserResource::make($user->load('profile')),
            'token' => $token
        ];
    }

    public function logout(User $user): bool
    {
        return $user->currentAccessToken()->delete();
    }

    public function getUserDetails(User $user): UserResource
    {
        return UserResource::make($user->load('profile'));
    }
    
}
```

### Arquivo: `Domains\Core\Services\AuditService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


class AuditService
{
    /**
     * Tipos de eventos de segurança
     */
    const EVENT_LOGIN_SUCCESS = 'login_success';
    const EVENT_LOGIN_FAILURE = 'login_failure';
    const EVENT_PERMISSION_DENIED = 'permission_denied';
    const EVENT_SENSITIVE_DATA_ACCESS = 'sensitive_data_access';
    const EVENT_ACCOUNT_CHANGE = 'account_change';
    const EVENT_API_KEY_USAGE = 'api_key_usage';
    const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    /**
     * @var AuditRepositoryInterface
     */
    protected $auditRepository;
    
    /**
     * Constructor
     * 
     * @param AuditRepositoryInterface $auditRepository
     */
    public function __construct(AuditRepositoryInterface $auditRepository)
    {
        $this->auditRepository = $auditRepository;
    }
    
    /**
     * Registra uma ação de auditoria
     *
     * @param string $action A ação realizada
     * @param string $entity Entidade relacionada à ação
     * @param array $data Dados associados à ação
     * @param Model|null $model Modelo associado à ação
     * @return \App\Models\Audit
     */
    public function log(string $action, string $entity, array $data = [], ?Model $model = null)
    {
        $userId = Auth::id();
        
        $auditData = [
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        
        if ($model) {
            $auditData['model_type'] = get_class($model);
            $auditData['model_id'] = $model->getKey();
        }
        
        return $this->auditRepository->create($auditData);
    }
    
    /**
     * Registra uma ação de criação
     *
     * @param Model $model Modelo criado
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logCreated(Model $model, array $data = [])
    {
        $entity = class_basename($model);
        return $this->log(
            'created',
            $entity,
            array_merge(['attributes' => $model->getAttributes()], $data),
            $model
        );
    }
    
    /**
     * Registra uma ação de atualização
     *
     * @param Model $model Modelo atualizado
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logUpdated(Model $model, array $data = [])
    {
        $entity = class_basename($model);
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        
        return $this->log(
            'updated',
            $entity,
            array_merge([
                'attributes' => $dirty,
                'original' => $original,
            ], $data),
            $model
        );
    }
    
    /**
     * Registra uma ação de exclusão
     *
     * @param Model $model Modelo excluído
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logDeleted(Model $model, array $data = [])
    {
        $entity = class_basename($model);
        return $this->log(
            'deleted',
            $entity,
            array_merge(['attributes' => $model->getAttributes()], $data),
            $model
        );
    }
    
    /**
     * Registra uma ação de login
     *
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logLogin(array $data = [])
    {
        return $this->log('login', 'Auth', $data);
    }
    
    /**
     * Registra uma ação de logout
     *
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logLogout(array $data = [])
    {
        return $this->log('logout', 'Auth', $data);
    }
    
    /**
     * Registra uma ação de falha de login
     *
     * @param string $username Nome de usuário que falhou
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logFailedLogin(string $username, array $data = [])
    {
        return $this->log(
            'failed_login',
            'Auth',
            array_merge(['username' => $username], $data)
        );
    }
    
    /**
     * Registra um evento de segurança
     *
     * @param string $eventType Tipo do evento de segurança
     * @param array $details Detalhes adicionais do evento
     * @param int|null $userId ID do usuário envolvido (null se não autenticado)
     * @return void
     */
    public function logSecurityEvent(string $eventType, array $details = [], ?int $userId = null): void
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);
        
        $eventData = [
            'event_type' => $eventType,
            'user_id' => $userId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'request_path' => Request::path(),
            'request_method' => Request::method(),
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Log detalhado para análise de segurança
        Log::channel('security')->info('Security event: ' . $eventType, $eventData);
        
        // Criar registro de auditoria
        $this->log(
            'security_' . $eventType,
            'Security',
            $eventData
        );
    }
    
    /**
     * Detecta atividades potencialmente suspeitas
     *
     * @param array $context Contexto da atividade a ser verificada
     * @return bool Se a atividade é considerada suspeita
     */
    public function detectSuspiciousActivity(array $context): bool
    {
        // Implementar lógicas de detecção de anomalias
        // Exemplos:
        // - Múltiplas falhas de login
        // - Acesso de localização incomum
        // - Padrões de acesso anormais
        // - Volume incomum de solicitações
        
        return false; // Implementação simplificada
    }
    
    /**
     * Verifica se o usuário atual tem permissão para ação específica
     * e registra tentativas não autorizadas
     *
     * @param string $action Ação sendo executada
     * @param mixed $resource Recurso sendo acessado
     * @return bool
     */
    public function checkAndLogAccess(string $action, $resource): bool
    {
        $user = Auth::user();
        
        if (!$user || !$user->can($action, $resource)) {
            $this->logSecurityEvent(self::EVENT_PERMISSION_DENIED, [
                'action' => $action,
                'resource_type' => is_object($resource) ? get_class($resource) : gettype($resource),
                'resource_id' => is_object($resource) && method_exists($resource, 'getKey') ? $resource->getKey() : null,
            ]);
            return false;
        }
        
        return true;
    }
}
```

### Arquivo: `Domains\Core\Services\BatchProcessorService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


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
```

### Arquivo: `Domains\Core\Services\CacheService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


class CacheService
{
    /**
     * Tempo padrão de cache em minutos
     */
    protected int $defaultTtl = 60;

    /**
     * Prefixo para as chaves de cache
     */
    protected string $prefix;

    /**
     * Construtor do serviço de cache
     */
    public function __construct(string $prefix = 'app_cache')
    {
        $this->prefix = $prefix;
    }

    /**
     * Obtém um item do cache ou executa o callback se não existir
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl * 60, $callback);
    }

    /**
     * Armazena um valor no cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::put($cacheKey, $value, $ttl * 60);
    }

    /**
     * Verifica se um item existe no cache
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::has($cacheKey);
    }

    /**
     * Obtém um item do cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::get($cacheKey, $default);
    }

    /**
     * Remove um item do cache
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::forget($cacheKey);
    }

    /**
     * Limpa o cache com base em um padrão de tag/prefixo
     */
    public function flush(string $pattern): bool
    {
        if (config('cache.default') === 'redis') {
            return $this->flushRedisPattern("{$this->prefix}:{$pattern}*");
        }

        // Para outros drivers, devemos usar uma abordagem diferente
        return Cache::flush();
    }

    /**
     * Limpa cache Redis com base em um padrão
     */
    protected function flushRedisPattern(string $pattern): bool
    {
        $redis = Cache::getRedis();
        $keys = $redis->keys($pattern);
        
        if (!empty($keys)) {
            $redis->del($keys);
        }
        
        return true;
    }

    /**
     * Gera uma chave de cache para um modelo
     */
    public function generateModelKey(Model $model): string
    {
        return get_class($model) . ':' . $model->getKey();
    }

    /**
     * Gera chave de cache para uma coleção de modelos
     */
    public function generateCollectionKey(Collection $collection, string $identifier = 'collection'): string
    {
        if ($collection->isEmpty()) {
            return class_basename($collection) . ':empty:' . $identifier;
        }

        $modelClass = get_class($collection->first());
        $ids = $collection->pluck($collection->first()->getKeyName())->sort()->implode(',');
        
        return $modelClass . ':' . $identifier . ':' . md5($ids);
    }

    /**
     * Formata uma chave de cache com o prefixo
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }

    /**
     * Define o TTL padrão
     */
    public function setDefaultTtl(int $minutes): self
    {
        $this->defaultTtl = $minutes;
        return $this;
    }
}
```

### Arquivo: `Domains\Core\Services\ExportService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


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
```

### Arquivo: `Domains\Core\Services\FeatureFlagService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;

// Removidos: UploadedFile, Storage, Log, Str

class FeatureFlagService
{    /**
     * Cache TTL in minutes
     * 
     * @var int
     */
    protected $cacheTtl = 60;

    /**
     * Get all feature flags
     *
     * @return Collection
     */
    public function getAllFeatures(): Collection
    {
        return Cache::remember('features.all', $this->cacheTtl, function () {
            return Feature::all();
        });
    }

    /**
     * Check if a feature is enabled
     *
     * @param string $featureName
     * @param int|null $userId
     * @return bool
     */
    public function isEnabled(string $featureName, ?int $userId = null): bool
    {
        // Check for specific user override
        if ($userId) {
            $userOverride = $this->getUserFeatureStatus($featureName, $userId);
            if ($userOverride !== null) {
                return $userOverride;
            }
        }

        // Check global feature status
        return Cache::remember("feature.{$featureName}", $this->cacheTtl, function () use ($featureName) {
            $feature = Feature::where('name', $featureName)->first();
            return $feature ? $feature->enabled : false; // Alterado de is_enabled para enabled
        });
    }
    
    /**
     * Cria ou atualiza uma feature flag.
     *
     * @param string $name
     * @param bool $enabled
     * @param string|null $description
     * @param DateTimeInterface|null $expiresAt
     * @return Feature|null
     */
    public function createOrUpdate(string $name, bool $enabled, ?string $description = null, ?DateTimeInterface $expiresAt = null): ?Feature
    {
        $feature = Feature::firstOrNew(['name' => $name]);
        $feature->enabled = $enabled;
        $feature->description = $description;
        $feature->expires_at = $expiresAt;
        $feature->save();

        Cache::forget("feature.{$name}");
        Cache::forget('features.all');

        return $feature;
    }

    /**
     * Obtém o status da feature para um usuário específico.
     *
     * @param string $featureName
     * @param int $userId
     * @return bool|null Retorna true/false se houver override, null caso contrário.
     */
    protected function getUserFeatureStatus(string $featureName, int $userId): ?bool
    {
        return Cache::remember("feature.{$featureName}.user.{$userId}", $this->cacheTtl, function () use ($featureName, $userId) {
            $feature = Feature::where('name', $featureName)->first();
            if (!$feature) {
                return null; // Feature não existe
            }
            $userSetting = $feature->users()->where('user_id', $userId)->first();
            return $userSetting ? (bool) $userSetting->pivot->enabled : null;
        });
    }

    /**
     * Ativa uma feature flag para um usuário específico.
     *
     * @param string $featureName
     * @param int $userId
     * @return bool
     */
    public function enableForUser(string $featureName, int $userId): bool
    {
        $feature = Feature::where('name', $featureName)->first();
        if (!$feature) {
            return false;
        }

        $feature->users()->syncWithoutDetaching([$userId => ['enabled' => true]]);
        Cache::forget("feature.{$featureName}.user.{$userId}");
        return true;
    }

    /**
     * Desativa uma feature flag para um usuário específico.
     *
     * @param string $featureName
     * @param int $userId
     * @return bool
     */
    public function disableForUser(string $featureName, int $userId): bool
    {
        $feature = Feature::where('name', $featureName)->first();
        if (!$feature) {
            return false;
        }

        $feature->users()->syncWithoutDetaching([$userId => ['enabled' => false]]);
        Cache::forget("feature.{$featureName}.user.{$userId}");
        return true;
    }

    /**
     * Lista todas as feature flags (método para o controller).
     *
     * @return Collection
     */
    public function listAll(): Collection
    {
        return $this->getAllFeatures();
    }
}
```

### Arquivo: `Domains\Core\Services\MonitoringService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


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
```

### Arquivo: `Domains\Core\Services\NotificationService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


class NotificationService
{
    /**
     * Envia notificação push para usuários específicos
     *
     * @param array $userIds IDs dos usuários
     * @param string $title Título da notificação
     * @param string $body Corpo da mensagem
     * @param array $data Dados adicionais
     * @return bool
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = []): bool
    {
        $tokens = User::whereIn('id', $userIds)
            ->whereNotNull('device_token')
            ->pluck('device_token')
            ->toArray();
            
        if (empty($tokens)) {
            return false;
        }
        
        return $this->sendToTokens($tokens, $title, $body, $data);
    }
    
    /**
     * Envia notificação para tokens específicos
     *
     * @param array $tokens Tokens de dispositivo
     * @param string $title Título da notificação
     * @param string $body Corpo da mensagem
     * @param array $data Dados adicionais
     * @return bool
     */
    public function sendToTokens(array $tokens, string $title, string $body, array $data = []): bool
    {
        try {
            // Aqui seria implementada a integração com FCM ou outro serviço
            
            // Log para simulação
            Log::info('Enviando notificação para tokens', [
                'tokens_count' => count($tokens),
                'title' => $title,
                'body' => $body,
                'data' => $data
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação para tokens: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia notificação para um tópico
     *
     * @param string $topic Tópico
     * @param string $title Título da notificação
     * @param string $body Corpo da mensagem
     * @param array $data Dados adicionais
     * @return bool
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            // Aqui seria implementada a integração com FCM ou outro serviço
            
            // Log para simulação
            Log::info('Enviando notificação para tópico', [
                'topic' => $topic,
                'title' => $title,
                'body' => $body,
                'data' => $data
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação para tópico: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Registra token de dispositivo para um usuário
     *
     * @param User $user Usuário
     * @param string $token Token do dispositivo
     * @param string $deviceType Tipo de dispositivo (android, ios, web)
     * @return bool
     */
    public function registerDeviceToken(User $user, string $token, string $deviceType): bool
    {
        try {
            $user->device_token = $token;
            $user->device_type = $deviceType;
            $user->save();
            
            Log::info('Token de dispositivo registrado', [
                'user_id' => $user->id,
                'device_type' => $deviceType,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao registrar token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Inscreve um token em um tópico
     *
     * @param string $token Token do dispositivo
     * @param string $topic Tópico
     * @return bool
     */
    public function subscribeToTopic(string $token, string $topic): bool
    {
        try {
            // Aqui seria implementada a inscrição no tópico via FCM
            
            // Log para simulação
            Log::info('Inscrevendo token em tópico', [
                'token_prefix' => substr($token, 0, 8) . '...',
                'topic' => $topic
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao inscrever em tópico: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancela a inscrição de um token em um tópico
     *
     * @param string $token Token do dispositivo
     * @param string $topic Tópico
     * @return bool
     */
    public function unsubscribeFromTopic(string $token, string $topic): bool
    {
        try {
            // Aqui seria implementada a desinscrição do tópico via FCM
            
            // Log para simulação
            Log::info('Cancelando inscrição de token em tópico', [
                'token_prefix' => substr($token, 0, 8) . '...',
                'topic' => $topic
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao cancelar inscrição em tópico: ' . $e->getMessage());
            return false;
        }
    }
}
```

### Arquivo: `Domains\Core\Services\QueueManagerService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


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
```

### Arquivo: `Domains\Core\Services\RateLimitService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


class RateLimitService
{
    /**
     * Verifica se uma solicitação excede os limites de taxa
     *
     * @param Request $request
     * @param User|null $user
     * @return array [exceedsLimit, remainingRequests, resetTime]
     */
    public function checkRateLimit(Request $request, ?User $user = null): array
    {
        $key = $this->getRateLimitKey($request, $user);
        $limit = $this->getLimitForKey($key, $user);
        $window = $this->getTimeWindowForKey($key, $user);
        
        // Chave Redis para o contador de requisições
        $redisKey = "rate_limit:{$key}";
        
        $current = Redis::get($redisKey) ?: 0;
        $current = (int) $current;
        $ttl = Redis::ttl($redisKey);
        
        // Se não há TTL ou é negativo, inicialize
        if ($ttl < 0) {
            Redis::set($redisKey, 1, 'EX', $window);
            $ttl = $window;
            $current = 1;
        } else {
            // Incrementa o contador
            $current = Redis::incr($redisKey);
        }
        
        // Calcula o tempo de reset
        $resetTime = now()->addSeconds($ttl);
        
        // Verifica se excede o limite
        $exceedsLimit = $current > $limit;
        
        // Calcula requisições restantes
        $remaining = max(0, $limit - $current);
        
        // Registra tentativas de excesso
        if ($exceedsLimit) {
            Log::channel('rate_limit')->warning('Rate limit exceeded', [
                'key' => $key,
                'ip' => $request->ip(),
                'path' => $request->path(),
                'user_id' => $user?->id,
            ]);
        }
        
        return [$exceedsLimit, $remaining, $resetTime];
    }
    
    /**
     * Obtém uma chave única para limitação de taxa
     *
     * @param Request $request
     * @param User|null $user
     * @return string
     */
    protected function getRateLimitKey(Request $request, ?User $user): string
    {
        // Se autenticado, use ID do usuário, caso contrário use IP
        if ($user) {
            $identifier = "user:{$user->id}";
        } else {
            $identifier = "ip:" . md5($request->ip());
        }
        
        // Adiciona endpoint para limites por rota
        $endpoint = "endpoint:" . md5($request->path());
        
        return "{$identifier}:{$endpoint}";
    }
    
    /**
     * Determina o limite apropriado para a chave
     *
     * @param string $key
     * @param User|null $user
     * @return int
     */
    protected function getLimitForKey(string $key, ?User $user): int
    {
        // Limites diferenciados por tipo de usuário
        if ($user) {
            if ($user->is_premium) {
                return 300; // Limite maior para usuários premium
            }
            return 100; // Limite padrão para usuários autenticados
        }
        
        return 30; // Limite para usuários não autenticados
    }
    
    /**
     * Determina a janela de tempo em segundos para a chave
     *
     * @param string $key
     * @param User|null $user
     * @return int
     */
    protected function getTimeWindowForKey(string $key, ?User $user): int
    {
        // Janelas de tempo padrão (em segundos)
        return 60; // 1 minuto
    }
}
```

### Arquivo: `Domains\Core\Services\WebhookService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\Core\Services;


class WebhookService
{
    /**
     * Dispatch a webhook to configured destinations
     *
     * @param string $event Event type
     * @param array $payload Data to send
     * @return void
     */
    public static function dispatch(string $event, array $payload)
    {
        $webhookUrls = config('webhooks.destinations');
        
        foreach ($webhookUrls as $url) {
            try {
                $response = Http::timeout(5)
                    ->withHeaders([
                        'User-Agent' => config('app.name') . ' Webhook',
                        'X-Webhook-Event' => $event,
                    ])
                    ->post($url, [
                        'event' => $event,
                        'payload' => $payload,
                        'timestamp' => now()->timestamp,
                    ]);
                
                if (!$response->successful()) {
                    Log::warning('Webhook delivery failed', [
                        'url' => $url,
                        'event' => $event,
                        'status' => $response->status(),
                        'response' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Webhook dispatch exception', [
                    'url' => $url,
                    'event' => $event,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Validate a webhook signature
     *
     * @param \Illuminate\Http\Request $request
     * @param string $secret
     * @return bool
     */
    public static function validateSignature($request, $secret)
    {
        $signature = $request->header('X-Hub-Signature-256');
        
        if (!$signature) {
            return false;
        }
        
        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
```

### Arquivo: `Domains\User\Commands\CreateUserCommand.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Commands;


class CreateUserCommand
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(UserDTO $userDTO): User
    {
        return $this->userRepository->createFromDTO($userDTO);
    }
}
```

### Arquivo: `Domains\User\Commands\DeleteUserCommand.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Commands;


class DeleteUserCommand
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}
```

### Arquivo: `Domains\User\Commands\UpdateUserCommand.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Commands;


class UpdateUserCommand
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(User $user, UserDTO $userDTO): bool
    {
        return $this->userRepository->updateFromDTO($user, $userDTO);
    }
}
```

### Arquivo: `Domains\User\Queries\GetAllUsersQuery.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Queries;


class GetAllUsersQuery
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }
}
```

### Arquivo: `Domains\User\Queries\GetUserByEmailQuery.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Queries;


class GetUserByEmailQuery
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }
}
```

### Arquivo: `Domains\User\Queries\GetUserByIdQuery.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Queries;


class GetUserByIdQuery
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(int $id): ?User
    {
        return $this->userRepository->findById($id);
    }
}
```

### Arquivo: `Domains\User\Services\UserService.php`


#### Conteúdo

```php
<?php

namespace App\Domains\User\Services;


class UserService
{
    protected CreateUserCommand $createUserCommand;
    protected UpdateUserCommand $updateUserCommand;
    protected DeleteUserCommand $deleteUserCommand;
    protected GetUserByIdQuery $getUserByIdQuery;
    protected GetAllUsersQuery $getAllUsersQuery;
    protected GetUserByEmailQuery $getUserByEmailQuery;
    
    public function __construct(
        CreateUserCommand $createUserCommand,
        UpdateUserCommand $updateUserCommand,
        DeleteUserCommand $deleteUserCommand,
        GetUserByIdQuery $getUserByIdQuery,
        GetAllUsersQuery $getAllUsersQuery,
        GetUserByEmailQuery $getUserByEmailQuery
    ) {
        $this->createUserCommand = $createUserCommand;
        $this->updateUserCommand = $updateUserCommand;
        $this->deleteUserCommand = $deleteUserCommand;
        $this->getUserByIdQuery = $getUserByIdQuery;
        $this->getAllUsersQuery = $getAllUsersQuery;
        $this->getUserByEmailQuery = $getUserByEmailQuery;
    }
    
    /**
     * Create a new user
     */
    public function createUser(UserDTO $userDTO): User
    {
        return $this->createUserCommand->handle($userDTO);
    }
    
    /**
     * Get user by ID
     */
    public function getUserById(int $id): ?User
    {
        return $this->getUserByIdQuery->handle($id);
    }
    
    /**
     * Get user with profile
     */
    public function getUserWithProfile(int $id): ?User
    {
        $user = $this->getUserById($id);
        return $user?->load('profile');
    }
    
    /**
     * Get all users paginated
     */
    public function getAllUsersPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->getAllUsersQuery->handle($perPage);
    }
    
    /**
     * Update user
     */
    public function updateUser(User $user, UserDTO $userDTO): bool
    {
        return $this->updateUserCommand->handle($user, $userDTO);
    }
    
    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        return $this->deleteUserCommand->handle($user);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?User
    {
        return $this->getUserByEmailQuery->handle($email);
    }
}
```

### Arquivo: `DTO\UserDTO.php`


#### Conteúdo

```php
<?php

namespace App\DTO;

class UserDTO
{
    /**
     * @var string
     */
    private string $name;
    
    /**
     * @var string
     */
    private string $email;
    
    /**
     * @var string|null
     */
    private ?string $password;
    
    /**
     * @var array
     */
    private array $profileData;
    
    /**
     * Create a new UserDTO instance.
     *
     * @param string $name
     * @param string $email
     * @param string|null $password
     * @param array $profileData
     */
    public function __construct(string $name, string $email, ?string $password = null, array $profileData = [])
    {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password;
        $this->profileData = $profileData;
    }
    
    /**
     * Create UserDTO from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $profileData = [];
        if (isset($data['profile'])) {
            $profileData = $data['profile'];
            unset($data['profile']);
        }
        
        return new self(
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? null,
            $profileData
        );
    }
    
    /**
     * Get user name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }
    
    /**
     * Get user email.
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }
    
    /**
     * Get user password.
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }
    
    /**
     * Get profile data.
     *
     * @return array
     */
    public function getProfileData(): array
    {
        return $this->profileData;
    }
    
    /**
     * Convert to array for database.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'email' => $this->email,
        ];
        
        if ($this->password) {
            $data['password'] = $this->password;
        }

        if (!empty($this->profileData)) {
            $data['profile'] = $this->profileData;
        }
        
        return $data;
    }
}
```

### Arquivo: `Http\Controllers\Api\V1\FeatureFlagController.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers\Api\V1;


class FeatureFlagController extends ApiController
{
    protected $featureFlagService;

    public function __construct(FeatureFlagService $featureFlagService)
    {
        $this->featureFlagService = $featureFlagService;
    }

    /**
     * Lista todas as feature flags
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $flags = $this->featureFlagService->listAll();
        return $this->successResponse($flags);
    }

    /**
     * Verifica status de uma feature flag
     * 
     * @param string $name Nome da feature flag
     * @return \Illuminate\Http\JsonResponse
     */
    public function check(string $name)
    {
        $userId = auth()->check() ? auth()->id() : null;
        $isActive = $this->featureFlagService->isActive($name, $userId);
        
        return $this->successResponse([
            'name' => $name,
            'is_active' => $isActive
        ]);
    }

    /**
     * Cria ou atualiza uma feature flag
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'is_active' => 'required|boolean',
            'percentage' => 'nullable|integer|min:0|max:100',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Dados de entrada inválidos', $validator->errors(), 422);
        }

        $result = $this->featureFlagService->createOrUpdate(
            $request->input('name'),
            $request->input('is_active'),
            $request->input('percentage', 0),
            $request->has('expires_at') ? new \DateTime($request->input('expires_at')) : null
        );

        if (!$result) {
            return $this->errorResponse('Não foi possível atualizar a feature flag');
        }

        return $this->successResponse(['message' => 'Feature flag atualizada com sucesso']);
    }

    /**
     * Ativa uma feature flag para um usuário específico
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function enableForUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Dados de entrada inválidos', $validator->errors(), 422);
        }

        $result = $this->featureFlagService->enableForUser(
            $request->input('name'),
            $request->input('user_id')
        );

        if (!$result) {
            return $this->errorResponse('Não foi possível ativar a feature para o usuário');
        }

        return $this->successResponse(['message' => 'Feature ativada para o usuário com sucesso']);
    }

    /**
     * Desativa uma feature flag para um usuário específico
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disableForUser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Dados de entrada inválidos', $validator->errors(), 422);
        }

        $result = $this->featureFlagService->disableForUser(
            $request->input('name'),
            $request->input('user_id')
        );

        if (!$result) {
            return $this->errorResponse('Não foi possível desativar a feature para o usuário');
        }

        return $this->successResponse(['message' => 'Feature desativada para o usuário com sucesso']);
    }
}
```

### Arquivo: `Http\Controllers\Api\V1\HealthCheckController.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers\Api\V1;


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
```

### Arquivo: `Http\Controllers\Api\V1\ProfileController.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers\Api\V1;


//Controller para armazenar dados secundários do usuário (bio, bairro...)
class ProfileController extends ApiController
{
    /**
     * Display the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        $user = $request->user();

        // Usar a policy para verificar se o usuário pode ver seu próprio perfil
        // $this->authorize('view', $user); // Implicitamente já é o usuário autenticado

        return response()->json(['data' => $user]);
    }

    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Usar a policy para verificar se o usuário pode atualizar seu próprio perfil
        $this->authorize('update', $user);

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:1000',
            // Adicionar outros campos de perfil conforme necessário
            // 'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id, // Se permitir edição de email
        ]);

        $user->update($validatedData);

        return response()->json(['message' => 'Profile updated successfully', 'data' => $user]);
    }
}
```

### Arquivo: `Http\Controllers\Api\V1\WebhookController.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers\Api\V1;


class WebhookController extends ApiController
{
    /**
     * Handle GitHub webhooks
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleGithub(Request $request)
    {
        $payload = $request->all();
        $event = $request->header('X-GitHub-Event');
        $secret = config('webhooks.github_secret');

        // Validate webhook signature
        if (!WebhookService::validateSignature($request, $secret)) {
            Log::warning('Invalid GitHub webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Process based on event type
        switch ($event) {
            case 'push':
                // Handle push event
                Log::info('GitHub push event received', ['repository' => $payload['repository']['name'] ?? 'unknown']);
                break;
            case 'pull_request':
                // Handle pull request event
                Log::info('GitHub PR event received', ['action' => $payload['action'] ?? 'unknown']);
                break;
            default:
                Log::info("GitHub event received: {$event}");
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Stripe webhooks
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleStripe(Request $request)
    {
        // Implement Stripe webhook handling
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle PayPal webhooks
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handlePaypal(Request $request)
    {
        // Implement PayPal webhook handling
        return response()->json(['status' => 'success']);
    }

    /**
     * Handle custom webhooks
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function handleCustom(Request $request)
    {
        // Implement custom webhook handling
        return response()->json(['status' => 'success']);
    }
}
```

### Arquivo: `Http\Controllers\ApiController.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers;


abstract class ApiController extends Controller
{
    /**
     * Default status code
     */
    protected int $statusCode = 200;

    /**
     * Set the status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Return a success response
     */
    public function successResponse($data = [], ?string $message = null, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? 'Operation successful',
            'data' => $this->transformData($data),
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Return an error response
     */
    public function errorResponse(string $message, int $statusCode = 400, $errors = null, string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response (201)
     */
    public function createdResponse($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->setStatusCode(201)->successResponse($data, $message);
    }

    /**
     * Return an updated response (200)
     */
    public function updatedResponse($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->successResponse($data, $message);
    }

    /**
     * Return a deleted response (200)
     */
    public function deletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->successResponse([], $message);
    }

    /**
     * Return a not found response (404)
     */
    public function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404, null, 'RESOURCE_NOT_FOUND');
    }

    /**
     * Return a validation error response (422)
     */
    public function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Return an unauthorized response (401)
     */
    public function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Return a forbidden response (403)
     */
    public function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Return a server error response (500)
     */
    public function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500, null, 'SERVER_ERROR');
    }

    /**
     * Return a paginated response
     */
    public function paginatedResponse($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        if ($paginator instanceof LengthAwarePaginator) {
            $data = $this->transformData($paginator->items());
            
            $meta = [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more_pages' => $paginator->hasMorePages(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ]
            ];

            return $this->successResponse($data, $message, $meta);
        }

        return $this->successResponse($paginator, $message);
    }

    /**
     * Transform data for consistent response format
     */
    private function transformData($data)
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        if ($data instanceof ResourceCollection) {
            return $data->resolve();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        if ($data instanceof LengthAwarePaginator) {
            return $this->transformData($data->items());
        }

        return $data;
    }
}
```

### Arquivo: `Http\Controllers\ApiDocumentationController.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers;


/**
 * @OA\Info(
 *     title="API do Template Flutter/Laravel",
 *     version="1.0.0",
 *     description="Documentação da API do template Flutter/Laravel",
 *     @OA\Contact(
 *         email="contato@exemplo.com.br",
 *         name="Equipe de Desenvolvimento"
 *     )
 * )
 * @OA\Server(
 *     description="Ambiente de Desenvolvimento",
 *     url=L5_SWAGGER_CONST_HOST
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class ApiDocumentationController extends Controller
{
    /**
     * Exibe a documentação da API
     */
    public function index()
    {
        return view('l5-swagger.index');
    }
}
```

### Arquivo: `Http\Controllers\Controller.php`


#### Conteúdo

```php
<?php

namespace App\Http\Controllers;


/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Flutter Laravel Template API",
 *     description="API documentation for Flutter Laravel Template",
 *     @OA\Contact(
 *         email="admin@example.com"
 *     ),
 *     @OA\License(
 *         name="GPL-3.0",
 *         url="https://www.gnu.org/licenses/gpl-3.0.en.html"
 *     )
 * ),
 * @OA\Server(
 *     url="/",
 *     description="API Server"
 * ),
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class Controller extends BaseController
{
}
```

### Arquivo: `Http\Middleware\ApiRateLimitMiddleware.php`


#### Conteúdo

```php
<?php

namespace App\Http\Middleware;


class ApiRateLimitMiddleware
{
    /**
    * O serviço de rate limit
    */
    protected RateLimitService $rateLimitService; // Adicione o tipo aqui
    
    /**
    * Construtor
    */
    public function __construct(RateLimitService $rateLimitService)
    {
        $this->rateLimitService = $rateLimitService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Usa o serviço unificado para evitar duplicação de lógica
        [$exceedsLimit, $remaining, $resetTime] = $this->rateLimitService->checkRateLimit($request, $request->user());
        
        if ($exceedsLimit) {
            return response()->json([
                'message' => 'Limite de requisições excedido. Por favor, aguarde antes de tentar novamente.',
                'retry_after' => $resetTime->diffInSeconds(now()),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }
        
        $response = $next($request);
        
        // Adiciona headers de rate limit
        $response->headers->add([
            'X-RateLimit-Limit' => $this->rateLimitService->limit ?? 60, // Use a default limit or property
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => $resetTime->timestamp,
        ]);
        
        return $response;
    }
}
```

### Arquivo: `Http\Middleware\CacheResponseMiddleware.php`


#### Conteúdo

```php
<?php

namespace App\Http\Middleware;


class CacheResponseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  int  $ttl  Tempo de cache em minutos
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $ttl = 60)
    {
        // Não aplica cache para métodos que modificam dados
        if (!$request->isMethodCacheable()) {
            return $next($request);
        }
        
        // Cria uma chave única baseada na URL e no usuário (se autenticado)
        $key = 'api_cache:' . md5($request->url() . 
               ($request->user() ? '_user_' . $request->user()->id : '_guest') .
               '_query_' . json_encode($request->query()));
        
        // Verifica se a resposta está em cache
        if (Cache::has($key)) {
            $cachedResponse = Cache::get($key);
            // Adiciona header indicando que veio do cache
            return response()->json(
                $cachedResponse['data'],
                $cachedResponse['status']
            )->header('X-API-Cache', 'HIT');
        }
        
        // Processa a requisição
        $response = $next($request);
        
        // Armazena a resposta em cache se for bem-sucedida
        if ($response->isSuccessful()) {
            $responseData = [
                'data' => json_decode($response->getContent(), true),
                'status' => $response->getStatusCode()
            ];
            Cache::put($key, $responseData, $ttl * 60);
        }
        
        // Adiciona header indicando que não veio do cache
        $response->headers->set('X-API-Cache', 'MISS');
        
        return $response;
    }
}
```

### Arquivo: `Http\Middleware\RefreshTokenMiddleware.php`


#### Conteúdo

```php
<?php

namespace App\Http\Middleware;


class RefreshTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Processa a requisição primeiro
        $response = $next($request);
        
        // Verifica se o usuário está autenticado
        if (Auth::check()) {
            $user = Auth::user();
            $token = $request->bearerToken();
            
            // Verifica se o token atual está próximo de expirar (menos de 30 min)
            $accessToken = PersonalAccessToken::findToken($token);
            if ($accessToken && $this->tokenNeedsRefresh($accessToken)) {
                // Cria um novo token
                $newToken = $user->createToken('api-refresh')->plainTextToken;
                
                // Adiciona o novo token no header da resposta
                $response->headers->set('X-New-Token', $newToken);
            }
        }
        
        return $response;
    }
    
    /**
     * Verifica se o token precisa ser atualizado
     */
    private function tokenNeedsRefresh($token): bool
    {
        // Verifica se o token expira em menos de 30 minutos
        // Ajuste conforme suas necessidades
        $expiresAt = $token->created_at->addMinutes(config('sanctum.expiration', 60));
        $refreshThreshold = now()->addMinutes(30);
        
        return $expiresAt->lt($refreshThreshold);
    }
}
```

### Arquivo: `Http\Requests\Auth\LoginRequest.php`


#### Conteúdo

```php
<?php

namespace App\Http\Requests\Auth;


class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ];
    }
}
```

### Arquivo: `Http\Requests\User\StoreUserRequest.php`


#### Conteúdo

```php
<?php

namespace App\Http\Requests\User;


class StoreUserRequest extends FormRequest
{

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'profile' => 'sometimes|array',
            'profile.bio' => 'sometimes|string|max:1000',
            'profile.phone' => 'sometimes|string|max:20',
        ];
    }

     public function messages()
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Formato de email inválido',
            'email.unique' => 'Este email já está em uso',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres',
            'password.confirmed' => 'As senhas não conferem',
        ];
    }
}
```

### Arquivo: `Http\Requests\User\UpdateUserRequest.php`


#### Conteúdo

```php
<?php

namespace App\Http\Requests\User;


class UpdateUserRequest extends FormRequest
{

    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->route('user'))
            ],
            'password' => 'sometimes|min:8|confirmed',
            'profile' => 'sometimes|array',
            'profile.bio' => 'sometimes|string|max:1000',
            'profile.phone' => 'sometimes|string|max:20',
        ];
    }
}
```

### Arquivo: `Http\Resources\ProfileResource.php`


#### Conteúdo

```php
<?php


namespace App\Http\Resources;


class ProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'avatar' => $this->avatar, // Consider returning a full URL if stored locally
            'bio' => $this->bio,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
```

### Arquivo: `Http\Resources\UserResource.php`


#### Conteúdo

```php
<?php

namespace App\Http\Resources;


class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            // Optionally include profile information if loaded
            'profile' => ProfileResource::make($this->whenLoaded('profile')),
        ];
    }
}
```

### Arquivo: `Jobs\BatchProcessor.php`


#### Conteúdo

```php
<?php

namespace App\Jobs;


class BatchProcessor implements ShouldQueue
{

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
```

### Arquivo: `Jobs\ProcessUserRegistration.php`


#### Conteúdo

```php
<?php

namespace App\Jobs;


class ProcessUserRegistration implements ShouldQueue
{

    protected User $user;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Exemplo de processamento em segundo plano após registro do usuário
        // Você pode adicionar lógica como:
        
        // 1. Criar perfil inicial do usuário
        // if (!$this->user->profile) {
        //     $profile = new Profile();
        //     $profile->user_id = $this->user->id;
        //     $profile->save();
        // }

        // 2. Enviar e-mail de boas-vindas
        SendWelcomeEmailJob::dispatch($this->user);

        // 3. Fazer qualquer processamento demorado
        // sleep(5); // Simulação de processamento demorado

        // 4. Registrar analytics de novo usuário
        logger("Processando registro em segundo plano para: {$this->user->email}");
    }
}
```

### Arquivo: `Jobs\QueueMonitoringJob.php`


#### Conteúdo

```php
<?php

namespace App\Jobs;


class QueueMonitoringJob implements ShouldQueue
{

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
```

### Arquivo: `Jobs\SendWelcomeEmailJob.php`


#### Conteúdo

```php
<?php

namespace App\Jobs;


class SendWelcomeEmailJob implements ShouldQueue
{

    /**
     * O usuário que acabou de se registrar.
     *
     * @var User
     */
    protected $user;

    /**
     * Create a new job instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Enviar o email de boas-vindas
            Mail::to($this->user->email)->send(new WelcomeEmail($this->user));
            
            // Registrar o sucesso do envio
            Log::info('Welcome email sent successfully', [
                'user_id' => $this->user->id,
                'email' => $this->user->email
            ]);
        } catch (\Exception $e) {
            // Registrar qualquer erro
            Log::error('Failed to send welcome email', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'error' => $e->getMessage()
            ]);
            
            // Decidir se deve tentar novamente com base no tipo de erro
            if ($this->shouldRetry($e)) {
                // Recolocar na fila após 10 minutos
                $this->release(600);
            } else {
                // Falha definitiva, não tentar novamente
                $this->fail($e);
            }
        }
    }
    
    /**
     * Determina se o job deve tentar novamente com base no tipo de erro.
     *
     * @param \Exception $e
     * @return bool
     */
    private function shouldRetry(\Exception $e)
    {
        // Podemos tentar novamente para erros de rede ou temporários
        $retryableErrors = [
            'Connection could not be established',
            'timeout',
            'Connection refused',
            'temporary error'
        ];
        
        foreach ($retryableErrors as $errorText) {
            if (stripos($e->getMessage(), $errorText) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
```

### Arquivo: `Mail\WelcomeEmail.php`


#### Conteúdo

```php
<?php

namespace App\Mail;


class WelcomeEmail extends Mailable
{

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * Create a new message instance.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('emails.welcome')
            ->subject('Bem-vindo ao ' . config('app.name') . '!')
            ->with([
                'name' => $this->user->name,
            ]);
    }
}
```

### Arquivo: `Models\Audit.php`


#### Conteúdo

```php
<?php

namespace App\Models;


class Audit extends Model
{

    /**
     * Os atributos que podem ser atribuídos em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'entity',
        'data',
        'model_type',
        'model_id',
        'ip_address',
        'user_agent',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
    ];

    /**
     * Obtém o usuário associado a este registro de auditoria.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtém o modelo auditado.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo('model');
    }
}
```

### Arquivo: `Models\Feature.php`


#### Conteúdo

```php
<?php

namespace App\Models;


class Feature extends Model
{

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'enabled',
        'description',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Os usuários que têm configurações específicas para esta feature.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'feature_user', 'feature_name', 'user_id')
            ->withPivot('enabled')
            ->withTimestamps()
            ->using(FeatureUser::class);
    }

    /**
     * Verifica se a feature está ativa para um usuário específico.
     *
     * @param User|int|null $user O usuário ou ID do usuário
     * @return bool
     */
    public function isEnabledForUser($user = null)
    {
        // Se não houver usuário, usa apenas a configuração global
        if (!$user) {
            return $this->enabled;
        }

        $userId = $user instanceof User ? $user->id : $user;

        // Verifica se há uma configuração específica para este usuário
        $userSetting = $this->users()->where('user_id', $userId)->first();

        if ($userSetting) {
            return (bool) $userSetting->pivot->enabled;
        }

        // Caso não haja configuração específica, usa a configuração global
        return $this->enabled;
    }
}
```

### Arquivo: `Models\FeatureUser.php`


#### Conteúdo

```php
<?php

namespace App\Models;


class FeatureUser extends Pivot
{
    /**
     * Indica se o modelo deve receber timestamps.
     *
     * @var bool
     */
    public $timestamps = true;
    
    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];
    
    /**
     * Get the feature that owns the pivot.
     */
    public function feature()
    {
        return $this->belongsTo(Feature::class, 'feature_name', 'name');
    }
    
    /**
     * Get the user that owns the pivot.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Arquivo: `Models\Profile.php`


#### Conteúdo

```php
<?php

namespace App\Models;


class Profile extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bio',
        'phone',
        'avatar',
        'address',
        'city',
        'state',
        'zip_code',
        'preferences'
    ];

    protected $casts = [
        'preferences' => 'array',
    ];

    /**
     * Get the user that owns the profile.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

### Arquivo: `Models\User.php`


#### Conteúdo

```php
<?php

namespace App\Models;


class User extends Authenticatable
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'device_token', // Adicionado para notificações push
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the user's profile.
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'feature_user', 'user_id', 'feature_name')
            ->withPivot('enabled')
            ->withTimestamps()
            ->using(FeatureUser::class);
    }
}
```

### Arquivo: `Policies\UserPolicy.php`


#### Conteúdo

```php
<?php

namespace App\Policies;


class UserPolicy
{

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        // Exemplo: Apenas administradores podem listar todos os usuários
        // return $user->isAdmin();
        return true; // Permitir para todos os usuários autenticados por enquanto
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model // O usuário que está sendo visualizado/editado
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        // Usuário pode ver seu próprio perfil ou se for admin
        return $user->id === $model->id; // || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        // Exemplo: Apenas administradores podem criar usuários diretamente
        // return $user->isAdmin();
        return false; // Geralmente o registro é público, não uma criação direta por outro usuário
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model // O usuário que está sendo atualizado
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, User $model)
    {
        // Usuário pode atualizar seu próprio perfil
        return $user->id === $model->id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function delete(User $user, User $model)
    {
        // Usuário pode deletar seu próprio perfil ou se for admin
        return $user->id === $model->id; // || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function restore(User $user, User $model)
    {
        // return $user->isAdmin();
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function forceDelete(User $user, User $model)
    {
        // return $user->isAdmin();
        return false;
    }
}
```

### Arquivo: `Providers\AuthServiceProvider.php`


#### Conteúdo

```php
<?php

        namespace App\Providers;

        // use Illuminate\Support\Facades\Gate; // Descomente se for usar Gates diretamente

        class AuthServiceProvider extends ServiceProvider
        {
            /**
             * The policy mappings for the application.
             *
             * @var array<class-string, class-string>
             */
            protected $policies = [
                User::class => UserPolicy::class,
                // Profile::class => ProfilePolicy::class, // Exemplo para um modelo Profile
            ];

            /**
             * Register any authentication / authorization services.
             */
            public function boot(): void
            {
                $this->registerPolicies();

                // Aqui você pode definir Gates se preferir para ações mais simples
                // Gate::define('update-post', function (User $user, Post $post) {
                //     return $user->id === $post->user_id;
                // });
            }
        }
```

### Arquivo: `Providers\DomainServiceProvider.php`


#### Conteúdo

```php
<?php

namespace App\Providers;


class DomainServiceProvider extends BaseServiceProvider
{
    // public $bindings = [
    //     // Apenas serviços com múltiplas implementações
    //     \App\Contracts\Services\NotificationServiceInterface::class => \App\Domains\Core\Services\NotificationService::class,
    //     \App\Contracts\Services\FileStorageServiceInterface::class => \App\Domains\Core\Services\FileStorageService::class,
    //     \App\Contracts\Services\ExportServiceInterface::class => \App\Domains\Core\Services\ExportService::class,
    // ];

    /**
     * Serviços de domínio que devem ser registrados como singletons.
     *
     * @var array
     */
    public $singletons = [
        \App\Domains\Auth\Services\AuthService::class,
        \App\Domains\Core\Services\AuditService::class,
        \App\Domains\Core\Services\CacheService::class,
        \App\Domains\Core\Services\ExportService::class,
        \App\Domains\Core\Services\FileStorageService::class,
        \App\Domains\Core\Services\MonitoringService::class,
        \App\Domains\Core\Services\QueueManagerService::class,
        \App\Domains\Core\Services\WebhookService::class,
        \App\Domains\Core\Services\NotificationService::class,
        \App\Domains\Core\Services\BatchProcessorService::class,
        \App\Domains\Core\Services\FeatureFlagService::class,
    ];

    /**
     * Bindings específicos de serviços de domínio (se houver interfaces).
     *
     * @var array
     */
    public $bindings = [
        // Adicione interfaces quando necessário:
        // \App\Contracts\Services\AuditServiceInterface::class => \App\Domains\Core\Services\AuditService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        // Os bindings e singletons acima são registrados automaticamente pelo Laravel
        // Sem necessidade de aliases - use sempre os namespaces corretos
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        //
    }
}
```

### Arquivo: `Providers\ExceptionServiceProvider.php`


#### Conteúdo

```php
<?php

namespace App\Providers;


class ExceptionServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(ExceptionHandler::class, Handler::class);
    }
}
```

### Arquivo: `Providers\RouteServiceProvider.php`


#### Conteúdo

```php
<?php

namespace App\Providers;


class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home'; // Ajuste conforme necessário, ou remova se não for usado para API.

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            // API Versioning routes
            Route::middleware('api')
                ->prefix('api/v1')
                ->group(function () {
                    require base_path('routes/api_v1.php');
                });

            // Legacy or current API version route
            Route::middleware(['api'])
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));

            // Health checks and webhooks remain outside versioning
            // Route::middleware(['api'])
            //     ->prefix('')
            //     ->group(base_path('routes/healthcheck.php'));

            // Route::middleware(['api'])
            //     ->prefix('')
            //     ->group(base_path('routes/webhooks.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip()); // Limite mais estrito para rotas de autenticação
        });
    }
}
```

### Arquivo: `Repositories\Eloquent\EloquentAuditRepository.php`


#### Conteúdo

```php
<?php

namespace App\Repositories\Eloquent;


class EloquentAuditRepository implements AuditRepositoryInterface
{
    /**
     * @var Audit
     */
    protected $model;

    /**
     * EloquentAuditRepository constructor.
     *
     * @param Audit $audit
     */
    public function __construct(Audit $audit)
    {
        $this->model = $audit;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Audit
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Audit
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByAction(string $action, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
```

### Arquivo: `Repositories\Eloquent\EloquentProfileRepository.php`


#### Conteúdo

```php
<?php

namespace App\Repositories\Eloquent;


class EloquentProfileRepository implements ProfileRepositoryInterface
{
    /**
     * @var Profile
     */
    protected $model;

    /**
     * EloquentProfileRepository constructor.
     *
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        $this->model = $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Profile
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId): ?Profile
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Profile
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Profile $profile, array $data): bool
    {
        return $profile->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Profile $profile): bool
    {
        return $profile->delete();
    }
}
```

### Arquivo: `Repositories\Interfaces\AuditRepositoryInterface.php`


#### Conteúdo

```php
<?php

namespace App\Repositories\Interfaces;


interface AuditRepositoryInterface
{
    /**
     * Obtém um registro de auditoria pelo ID.
     *
     * @param int $id
     * @return Audit|null
     */
    public function findById(int $id): ?Audit;
    
    /**
     * Cria um novo registro de auditoria.
     *
     * @param array $data
     * @return Audit
     */
    public function create(array $data): Audit;
    
    /**
     * Obtém registros de auditoria com paginação.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Obtém registros de auditoria filtrados por usuário.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Obtém registros de auditoria filtrados por tipo de ação.
     *
     * @param string $action
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAction(string $action, int $perPage = 15): LengthAwarePaginator;
}
```

### Arquivo: `Repositories\Interfaces\ProfileRepositoryInterface.php`


#### Conteúdo

```php
<?php

namespace App\Repositories\Interfaces;


interface ProfileRepositoryInterface
{
    /**
     * Obtém um perfil pelo ID.
     *
     * @param int $id
     * @return Profile|null
     */
    public function findById(int $id): ?Profile;
    
    /**
     * Obtém um perfil pelo ID do usuário.
     *
     * @param int $userId
     * @return Profile|null
     */
    public function findByUserId(int $userId): ?Profile;
    
    /**
     * Cria um novo perfil.
     *
     * @param array $data
     * @return Profile
     */
    public function create(array $data): Profile;
    
    /**
     * Atualiza um perfil existente.
     *
     * @param Profile $profile
     * @param array $data
     * @return bool
     */
    public function update(Profile $profile, array $data): bool;
    
    /**
     * Exclui um perfil.
     *
     * @param Profile $profile
     * @return bool
     */
    public function delete(Profile $profile): bool;
}
```

