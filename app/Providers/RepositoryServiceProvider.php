<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bindings de interfaces de repositórios para suas implementações.
     *
     * @var array
     */
    public $bindings = [
        \App\Repositories\Interfaces\UserRepositoryInterface::class => \App\Repositories\Eloquent\EloquentUserRepository::class,
        \App\Repositories\Interfaces\ProfileRepositoryInterface::class => \App\Repositories\Eloquent\EloquentProfileRepository::class,
        \App\Repositories\Interfaces\AuditRepositoryInterface::class => \App\Repositories\Eloquent\EloquentAuditRepository::class,
    ];

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Os bindings acima são registrados automaticamente pelo Laravel
        // Adicione registros manuais específicos aqui se necessário
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}