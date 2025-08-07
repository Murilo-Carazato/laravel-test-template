<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

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
