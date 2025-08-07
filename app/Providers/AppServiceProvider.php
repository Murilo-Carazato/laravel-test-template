<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Carrega configurações do ambiente diretamente do arquivo de configuração
        $environment = app()->environment();
        if (config()->has("environments.{$environment}")) {
            $this->applyEnvironmentSettings($environment);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Código existente de inicialização
    }

    /**
     * Aplica as configurações específicas de ambiente
     */
    private function applyEnvironmentSettings(string $environment): void
    {
        $envConfig = config("environments.{$environment}");
        
        // Aplicar configurações de depuração
        if (isset($envConfig['debug'])) {
            config(['app.debug' => $envConfig['debug']]);
        }
        
        // Configurar throttling da API
        if (isset($envConfig['api_throttle']) && $envConfig['api_throttle']['enabled']) {
            config([
                'api.throttle.enabled' => true,
                'api.throttle.max_attempts' => $envConfig['api_throttle']['max_attempts'],
                'api.throttle.decay_minutes' => $envConfig['api_throttle']['decay_minutes'],
            ]);
        }
        
        // Configurar CORS se especificado
        if (isset($envConfig['cors']['allowed_origins'])) {
            config(['cors.allowed_origins' => $envConfig['cors']['allowed_origins']]);
        }
        
        // Configurar driver de email se especificado
        if (isset($envConfig['mail']['driver'])) {
            config(['mail.default' => $envConfig['mail']['driver']]);
        }
    }
}