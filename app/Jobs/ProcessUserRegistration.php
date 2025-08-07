<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeEmail;

class ProcessUserRegistration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
