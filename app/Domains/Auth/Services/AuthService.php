<?php

namespace App\Domains\Auth\Services;

use App\Models\User;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Domains\Auth\Contracts\AuthServiceInterface;

class AuthService implements AuthServiceInterface
{
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        // Cria um perfil vazio para o usuário
        $user->profile()->create();

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => UserResource::make($user),
            'token' => $token
        ];
    }

    public function login(array $credentials): ?array
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();
        
        // Revoga tokens anteriores e cria um novo
        $user->tokens()->delete();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => UserResource::make($user->load('profile')),
            'token' => $token
        ];
    }

    public function logout(User $user): bool
    {
        return $user->currentAccessToken()->delete();
    }

    public function getUserDetails(User $user): UserResource
    {
        return UserResource::make($user->load('profile'));
    }
    
}