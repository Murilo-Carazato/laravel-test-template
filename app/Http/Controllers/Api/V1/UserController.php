<?php

namespace App\Http\Controllers\Api\V1;

use App\DTO\UserDTO;
use App\Http\Controllers\ApiController;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use App\Domains\User\Services\UserService;
use App\Models\User;

class UserController extends ApiController
{
    public function __construct(
        protected UserService $userService,
    ) {
        // Ativar autorização usando UserPolicy
        // $this->authorizeResource(User::class, 'user');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->get('per_page', 15);
        $users = $this->userService->getAllUsersPaginated($perPage);

        return $this->paginatedResponse(
            UserResource::collection($users),
            'Users retrieved successfully'
        );
    }

    public function show(User $user): JsonResponse
    {
        $userWithProfile = $this->userService->getUserWithProfile($user->id);

        return $this->successResponse(
            new UserResource($userWithProfile),
            'User retrieved successfully'
        );
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $userDTO = UserDTO::fromArray($request->validated());
        $user = $this->userService->createUser($userDTO);

        return $this->createdResponse(
            new UserResource($user),
            'User created successfully'
        );
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {

        $userDTO = UserDTO::fromArray($request->validated());
        $updatedUser = $this->userService->updateUser($user, $userDTO);

        return $this->updatedResponse(
            new UserResource($updatedUser),
            'User updated successfully'
        );
    }

    public function destroy(User $user): JsonResponse
    {
        $this->userService->deleteUser($user);

        return $this->deletedResponse('User deleted successfully');
    }
}
