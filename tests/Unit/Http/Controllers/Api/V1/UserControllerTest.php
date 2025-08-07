<?php

namespace Tests\Unit\Http\Controllers\Api\V1;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\UserController; // Caminho completo para o controller
use App\Domains\User\Services\UserService;
use App\Domains\Core\Services\AuditService;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use App\DTO\UserDTO; // Importar DTO
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator; // Importar LengthAwarePaginator
use Illuminate\Http\Request; // Importar Request

class UserControllerTest extends TestCase
{
    protected $auditService;
    protected $userService;
    protected $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userService = Mockery::mock(UserService::class);
        $this->auditService = Mockery::mock(AuditService::class);
        // Instancia o controller com os mocks
        $this->controller = new UserController($this->auditService, $this->userService);
    }

    /** @test */
    public function test_index_returns_paginated_users()
    {
        // Arrange
        $users = User::factory()->count(3)->make();
        $paginator = new LengthAwarePaginator($users, 3, 15, 1);

        $this->userService
            ->shouldReceive('getAllUsersPaginated') // Chamar o método correto do service
            ->once()
            ->with(15) // Esperar o perPage padrão
            ->andReturn($paginator);

        $request = new Request();
        $response = $this->controller->index($request);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'email'] // Simplificado para exemplo, pode ser mais detalhado
            ],
            'meta' => [
                'pagination' => ['total', 'current_page', 'per_page']
            ]
        ]);
    }

    /** @test */
    public function test_show_returns_user_by_id()
    {
        // Arrange
        $user = User::factory()->make(['id' => 1]); // Use make para não persistir no DB real

        $this->userService
            ->shouldReceive('getUserById')
            ->with(1)
            ->once()
            ->andReturn($user);

        // Act
        $response = $this->controller->show(1);

        // Assert
        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => 1, 'name' => $user->name]);
    }

    /** @test */
    public function test_show_returns_404_if_user_not_found()
    {
        // Arrange
        $this->userService
            ->shouldReceive('getUserById')
            ->with(999)
            ->once()
            ->andReturn(null);

        // Act
        $response = $this->controller->show(999);

        // Assert
        $response->assertStatus(404);
        $response->assertJson(['message' => 'Resource not found']);
    }

    /** @test */
    public function test_store_creates_new_user()
    {
        // Arrange
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $user = User::factory()->make($userData); // Use make para não persistir

        $request = Mockery::mock(StoreUserRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($userData);
        
        $this->auditService->shouldReceive('logCreated')->once()->andReturnTrue();


        $this->userService
            ->shouldReceive('createUser')
            ->once()
            ->with(Mockery::on(function (UserDTO $dto) use ($userData) {
                return $dto->getName() === $userData['name'] &&
                       $dto->getEmail() === $userData['email'] &&
                       $dto->getPassword() === $userData['password'];
            }))
            ->andReturn($user);

        // Act
        $response = $this->controller->store($request);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonFragment(['name' => 'Test User', 'email' => 'test@example.com']);
    }

    /** @test */
    public function test_update_modifies_existing_user()
    {
        // Arrange
        $user = User::factory()->create(['id' => 1]); // Crie um user real para passar para o controller
        $updateData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];

        $request = Mockery::mock(UpdateUserRequest::class);
        $request->shouldReceive('validated')
            ->once()
            ->andReturn($updateData);

        // O serviço espera um objeto User e um DTO
        $this->userService
            ->shouldReceive('updateUser')
            ->once()
            ->withArgs(function (User $userArg, UserDTO $dtoArg) use ($user, $updateData) {
                return $userArg->id === $user->id &&
                       $dtoArg->getName() === $updateData['name'] &&
                       $dtoArg->getEmail() === $updateData['email'];
            })
            ->andReturn(true); // update retorna bool agora

        $this->auditService->shouldReceive('logUpdated')->once()->andReturnTrue();

        // Act
        // O controller agora recebe o objeto User via Route Model Binding
        $response = $this->controller->update($request, $user);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['message' => 'Resource updated successfully']);
    }

    /** @test */
    public function test_update_returns_404_if_user_not_found()
    {
        // Arrange
        $updateData = ['name' => 'Updated Name'];
        $request = Mockery::mock(UpdateUserRequest::class);
        $request->shouldReceive('validated')->andReturn($updateData);

        // Moca um User "falso" para o Route Model Binding falhar
        $nonExistentUser = Mockery::mock(User::class);
        $nonExistentUser->shouldReceive('getAttribute')->with('id')->andReturn(999);
        $nonExistentUser->shouldReceive('exists')->andReturn(false); // Para simular que não existe no DB

        // Neste cenário, o service não será chamado porque o Route Model Binding falharia antes
        // Ou, se o controller for robusto e chamar um find, mockar null para o find.
        // Como o controller espera um objeto User, a validação de "não encontrado" seria na camada superior.
        // Este teste é mais apropriado para um Feature Test. Para um Unit Test, focamos na lógica *dentro* do controller.
        // Portanto, para simular um "not found" no Unit Test, precisaríamos mockar um repositório que retorna null,
        // mas o update do controller não busca o user (ele recebe).

        // Ajustando o teste para o cenário unitário: se updateUser retorna false
        $existingUser = User::factory()->create(['id' => 1]);
        $this->userService
            ->shouldReceive('updateUser')
            ->once()
            ->andReturn(false); // Simula que a atualização falhou por alguma razão no service

        $response = $this->controller->update($request, $existingUser);

        $response->assertStatus(500); // Ou outro status de erro que o service retornaria
    }


    /** @test */
    public function test_destroy_deletes_user()
    {
        // Arrange
        $user = User::factory()->create(['id' => 1]); // Crie um user real

        $this->userService
            ->shouldReceive('deleteUser')
            ->with($user) // O service espera o objeto User
            ->once()
            ->andReturn(true);
        
        $this->auditService->shouldReceive('logDeleted')->once()->andReturnTrue();


        // Act
        $response = $this->controller->destroy($user);

        // Assert
        $response->assertStatus(200); // 200 para deletedResponse
        $response->assertJson(['message' => 'Resource deleted successfully']);
    }

    /** @test */
    public function test_destroy_returns_404_if_user_not_found()
    {
        // Arrange
        $nonExistentUser = Mockery::mock(User::class);
        $nonExistentUser->shouldReceive('getAttribute')->with('id')->andReturn(999);
        $nonExistentUser->shouldReceive('exists')->andReturn(false); 

        // O controller agora espera um objeto User. Se o Route Model Binding falhar,
        // o Laravel já lançaria uma exceção ModelNotFoundException antes do controller ser chamado.
        // Para simular isso em um Unit Test, precisaríamos mockar o Route Model Binding,
        // o que é mais complexo e pode ser mais bem testado em um Feature Test.

        // Simulação de falha no service
        $user = User::factory()->create(['id' => 1]); // User existente para simular falha interna
        $this->userService
            ->shouldReceive('deleteUser')
            ->with($user)
            ->once()
            ->andReturn(false); // Simula que a exclusão falhou no service

        $this->auditService->shouldReceive('logDeleted')->never(); // Não deve ser logado se a exclusão falha
        
        // Act
        $response = $this->controller->destroy($user);

        // Assert
        $response->assertStatus(500); // Ou o status de erro apropriado para falha no service
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}