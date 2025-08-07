<?php

namespace App\Domains\User\Commands;

use App\DTO\UserDTO;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UpdateUserCommand
{
    protected UserRepositoryInterface $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle(User $user, UserDTO $userDTO): bool
    {
        return $this->userRepository->updateFromDTO($user, $userDTO);
    }
}