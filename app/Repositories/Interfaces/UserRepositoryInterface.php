<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use App\DTO\UserDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface
{
    /**
     * Obtém um usuário pelo ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User;
    
    /**
     * Obtém um usuário pelo email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;
    
    /**
     * Cria um novo usuário a partir de um DTO.
     *
     * @param UserDTO $userDTO
     * @return User
     */
    public function createFromDTO(UserDTO $userDTO): User;
    
    /**
     * Atualiza um usuário existente a partir de um DTO.
     *
     * @param User $user
     * @param UserDTO $userDTO
     * @return bool
     */
    public function updateFromDTO(User $user, UserDTO $userDTO): bool;
    
    /**
     * Exclui um usuário.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool;
    
    /**
     * Obtém todos os usuários com paginação.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    /**
     * Obtém todos os usuários com paginação e filtros opcionais.
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function getAll(int $perPage = 15, array $filters = []): LengthAwarePaginator;
}