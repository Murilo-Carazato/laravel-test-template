<?php

namespace App\Domains\User\Commands;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class DeleteUserCommand
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}