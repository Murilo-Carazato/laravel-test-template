<?php

namespace App\Domains\Auth\Commands;

use App\Domains\Auth\Services\AuthService;
use App\DTO\UserDTO;

class LoginCommand
{
    protected $authService;
    
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    
    public function handle(string $email, string $password): array
    {
        return $this->authService->authenticate($email, $password);
    }
}
