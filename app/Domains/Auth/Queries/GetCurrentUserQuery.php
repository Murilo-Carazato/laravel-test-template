<?php

namespace App\Domains\Auth\Queries;

use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;

class GetCurrentUserQuery
{
    protected $userRepository;
    
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    
    public function handle()
    {
        $user = Auth::user();
        return $user ? $this->userRepository->find($user->id) : null;
    }
}
