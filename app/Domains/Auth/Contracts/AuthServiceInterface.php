<?php

namespace App\Domains\Auth\Contracts;

use App\Models\User;

interface AuthServiceInterface
{
    /**
     * Registra um novo usuário
     *
     * @param array $data
     * @return array
     */
    public function register(array $data): array;

    /**
     * Autentica um usuário
     *
     * @param array $credentials
     * @return array|null
     */
    public function login(array $credentials): ?array;

    /**
     * Desconecta um usuário
     *
     * @param User $user
     * @return bool
     */
    public function logout(User $user): bool;
}