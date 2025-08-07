<?php

namespace Tests\Unit\Http\Requests\User; // Namespace corrigido

use Tests\TestCase;
use App\Http\Requests\User\StoreUserRequest; // Importar o Request
use Illuminate\Support\Facades\Validator; // Importar Validator

class StoreUserRequestTest extends TestCase
{
    /** @test */
    public function test_valid_data_passes_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile' => [
                'bio' => 'A short bio',
                'phone' => '1234567890'
            ]
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_required_fields_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_format_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_unique_validation(): void
    {
        // Arrange
        \App\Models\User::factory()->create(['email' => 'existing@example.com']);
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'existing@example.com', // Duplicated email
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_confirmation_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_minimum_length_validation(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short', // less than 8 chars
            'password_confirmation' => 'short'
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_profile_fields_are_sometimes_array(): void
    {
        // Arrange
        $request = new StoreUserRequest();
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'profile' => [
                'bio' => 123, // Invalid type
                'phone' => str_repeat('a', 21) // Too long
            ]
        ];

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('profile.bio', $validator->errors()->toArray());
        $this->assertArrayHasKey('profile.phone', $validator->errors()->toArray());
    }

    /** @test */
    public function test_messages_are_returned(): void
    {
        $request = new StoreUserRequest();
        $data = [
            'email' => 'invalid',
            'password' => 'short',
            'password_confirmation' => 'mismatch'
        ];

        $validator = Validator::make($data, $request->rules());
        $messages = $validator->errors()->toArray();

        $this->assertEquals('O nome é obrigatório', $messages['name'][0]);
        $this->assertEquals('Formato de email inválido', $messages['email'][0]);
        $this->assertEquals('A senha deve ter pelo menos 8 caracteres', $messages['password'][0]);
        $this->assertEquals('As senhas não conferem', $messages['password'][1]);
    }
}