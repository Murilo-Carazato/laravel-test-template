<?php

namespace App\Jobs;

use App\Models\User;
use App\Mail\WelcomeEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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