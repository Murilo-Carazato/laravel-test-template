<?php

namespace App\Repositories\Interfaces;

use App\Models\Profile;
use App\Models\User;

interface ProfileRepositoryInterface
{
    /**
     * Obtém um perfil pelo ID.
     *
     * @param int $id
     * @return Profile|null
     */
    public function findById(int $id): ?Profile;
    
    /**
     * Obtém um perfil pelo ID do usuário.
     *
     * @param int $userId
     * @return Profile|null
     */
    public function findByUserId(int $userId): ?Profile;
    
    /**
     * Cria um novo perfil.
     *
     * @param array $data
     * @return Profile
     */
    public function create(array $data): Profile;
    
    /**
     * Cria um perfil para um usuário específico.
     *
     * @param User $user
     * @param array $data
     * @return Profile
     */
    public function createForUser(User $user, array $data): Profile;
    
    /**
     * Atualiza um perfil existente.
     *
     * @param Profile $profile
     * @param array $data
     * @return bool
     */
    public function update(Profile $profile, array $data): bool;
    
    /**
     * Atualiza ou cria um perfil para um usuário.
     *
     * @param User $user
     * @param array $data
     * @return Profile
     */
    public function updateOrCreateForUser(User $user, array $data): Profile;
    
    /**
     * Exclui um perfil.
     *
     * @param Profile $profile
     * @return bool
     */
    public function delete(Profile $profile): bool;
}