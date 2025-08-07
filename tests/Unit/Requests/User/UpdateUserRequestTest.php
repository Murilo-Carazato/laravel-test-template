<?php

namespace Tests\Unit\Http\Requests\User; // Namespace corrigido

use Tests\TestCase;
use App\Http\Requests\User\UpdateUserRequest; // Importar o Request
use Illuminate\Support\Facades\Validator; // Importar Validator
use App\Models\User; // Importar User
use Illuminate\Routing\Route; // Importar Route para mock
use Mockery;

class UpdateUserRequestTest extends TestCase
{
    /** @test */
    public function test_valid_data_passes_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'name' => 'John Updated',
            'email' => 'john.updated@example.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'profile' => [
                'bio' => 'An updated bio',
                'phone' => '0987654321'
            ]
        ];

        // Mock the route parameter 'user' for unique email rule
        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function test_name_is_sometimes_required_and_string(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'name' => '', // Fails 'required' and 'string' implicitly
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->toArray());
        $this->assertStringContainsString('The name field is required', $validator->errors()->first('name'));
    }

    /** @test */
    public function test_name_is_optional_if_not_provided(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'john.updated@example.com'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('name', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_is_optional_if_not_provided(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'name' => 'John Updated'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails());
        $this->assertArrayNotHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_format_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'invalid-email'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_email_uniqueness_validation_excludes_current_user(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'user1@example.com' // Email of current user
        ];

        $this->mockRouteParameter($request, $user1);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertFalse($validator->fails()); // Should pass because it's the current user's email
    }

    /** @test */
    public function test_email_uniqueness_validation_fails_for_other_users(): void
    {
        // Arrange
        $user1 = User::factory()->create(['email' => 'user1@example.com']);
        $user2 = User::factory()->create(['email' => 'user2@example.com']);
        
        $request = new UpdateUserRequest();
        $data = [
            'email' => 'user2@example.com' // Email of another user
        ];

        $this->mockRouteParameter($request, $user1);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_minimum_length_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'password' => 'short', // Too short
            'password_confirmation' => 'short'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /** @test */
    public function test_password_confirmation_validation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $request = new UpdateUserRequest();
        $data = [
            'password' => 'password123',
            'password_confirmation' => 'different_password'
        ];

        $this->mockRouteParameter($request, $user);

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Helper to mock route parameter for Rule::unique()
     */
    protected function mockRouteParameter(UpdateUserRequest $request, User $user): void
    {
        $route = Mockery::mock(Route::class);
        $route->shouldReceive('parameter')->with('user')->andReturn($user);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}