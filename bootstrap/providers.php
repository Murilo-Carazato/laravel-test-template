<?php

return [
    // Core providers (sempre primeiro)
    App\Providers\AppServiceProvider::class,
    App\Providers\ExceptionServiceProvider::class,

    // Infrastructure providers
    App\Providers\RepositoryServiceProvider::class,
    App\Providers\DomainServiceProvider::class,

    // Authentication & Authorization
    App\Providers\AuthServiceProvider::class,

    // Schedule (se criado)
    // App\Providers\ScheduleServiceProvider::class,

    // Routes (sempre por último)
    App\Providers\RouteServiceProvider::class,
];
