<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\ProcessUserRegistration;
use App\Jobs\SendWelcomeEmailJob;
use App\Models\User;
use Illuminate\Support\Facades\Queue; // Importar Queue
use Illuminate\Support\Facades\Log; // Importar Log

class ProcessUserRegistrationTest extends TestCase
{
    /** @test */
    public function test_handle_dispatches_send_welcome_email_job_successfully(): void
    {
        // Arrange
        Queue::fake(); // Para mocar a fila e verificar se o job foi despachado
        Log::fake(); // Para mocar o log

        $user = User::factory()->create();
        $job = new ProcessUserRegistration($user);

        // Act
        $job->handle();

        // Assert
        // Verifica se o SendWelcomeEmailJob foi despachado para a fila
        Queue::assertPushed(SendWelcomeEmailJob::class, function ($job) use ($user) {
            return $job->user->id === $user->id;
        });

        // Verifica se a mensagem de log foi gerada
        Log::assertLogged('info', function ($message, $context) use ($user) {
            return str_contains($message, 'Processando registro em segundo plano') &&
                   $context['user_id'] === $user->id; // Adicionado user_id ao log
        });
    }

    /** @test */
    public function test_job_can_be_serialized(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new ProcessUserRegistration($user);

        // Act
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(ProcessUserRegistration::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
    }
}