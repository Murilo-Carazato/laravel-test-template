<?php

namespace App\Domains\User\Queries;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class GetUserWithProfileQuery
{
    public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}

    public function handle(int $id): ?User
    {
        $user = $this->userRepository->findById($id);
        
        if (!$user) return null;
        
        // ✅ Sempre carrega profile básico
        return $user->load('profile');
    }
}