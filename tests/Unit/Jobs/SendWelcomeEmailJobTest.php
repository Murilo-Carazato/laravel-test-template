<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\SendWelcomeEmailJob;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Mail; // Importar Mail
use Illuminate\Support\Facades\Log; // Importar Log
use Mockery; // Importar Mockery
use Exception; // Importar Exception

class SendWelcomeEmailJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake(); // Falsifica o envio de emails
        Log::fake(); // Falsifica o log
    }

    /** @test */
    public function it_sends_welcome_email_to_user(): void
    {
        // Arrange
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ]);

        $job = new SendWelcomeEmailJob($user);

        // Act
        $job->handle();

        // Assert
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email) &&
                $mail->user->id === $user->id;
        });

        // Verifica que o log de sucesso foi gerado
        Log::assertLogged('info', function ($message, $context) use ($user) {
            return str_contains($message, 'Welcome email sent successfully') &&
                $context['user_id'] === $user->id &&
                $context['email'] === $user->email;
        });
    }

    /** @test */
    public function test_job_can_be_serialized(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new SendWelcomeEmailJob($user);

        // Act
        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        // Assert
        $this->assertInstanceOf(SendWelcomeEmailJob::class, $unserialized);
        $this->assertEquals($user->id, $unserialized->user->id);
    }

    /** @test */
    public function test_job_retries_on_temporary_mail_service_failure(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new SendWelcomeEmailJob($user);
        $exception = new Exception('Connection could not be established');

        // Usar reflection para acessar o método privado
        $reflectionClass = new \ReflectionClass($job);
        $shouldRetryMethod = $reflectionClass->getMethod('shouldRetry');
        $shouldRetryMethod->setAccessible(true);

        // Assert - verifica se deve tentar novamente
        $this->assertTrue($shouldRetryMethod->invoke($job, $exception));
    }

    /** @test */
    public function test_job_fails_definitively_on_non_retryable_exception(): void
    {
        // Arrange
        $user = User::factory()->create();
        $job = new SendWelcomeEmailJob($user);
        $exception = new Exception('Invalid email format');

        // Usar reflection para acessar o método privado
        $reflectionClass = new \ReflectionClass($job);
        $shouldRetryMethod = $reflectionClass->getMethod('shouldRetry');
        $shouldRetryMethod->setAccessible(true);

        // Assert - verifica se NÃO deve tentar novamente
        $this->assertFalse($shouldRetryMethod->invoke($job, $exception));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
