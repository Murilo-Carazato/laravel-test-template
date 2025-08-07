<?php

namespace App\Domains\User\Commands;

use App\DTO\UserDTO;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class CreateUserCommand
{
   public function __construct(
        protected UserRepositoryInterface $userRepository
    ) {}
    
    public function handle(UserDTO $userDTO): User
    {
        return $this->userRepository->createFromDTO($userDTO);
    }
}