<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

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
