<?php

namespace App\Domains\User\Services;

use App\Domains\User\Commands\CreateUserCommand;
use App\Domains\User\Commands\UpdateUserCommand;
use App\Domains\User\Commands\DeleteUserCommand;
use App\Domains\User\Queries\GetAllUsersQuery;
use App\Domains\User\Queries\GetUserWithProfileQuery;
use App\Domains\Core\Services\AuditService;
use App\DTO\UserDTO;
use App\Models\User;
use App\Domains\Core\Services\FeatureFlagService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Interfaces\ProfileRepositoryInterface;
use Exception;

class UserService
{
    public function __construct(
        protected CreateUserCommand $createUserCommand,
        protected UpdateUserCommand $updateUserCommand,
        protected DeleteUserCommand $deleteUserCommand,
        protected GetAllUsersQuery $getAllUsersQuery,
        protected GetUserWithProfileQuery $getUserWithProfileQuery,
        protected FeatureFlagService $featureFlagService,
        protected AuditService $auditService,
        protected ProfileRepositoryInterface $profileRepository,
    ) {}

    /**
     * Create a new user
     */
    public function createUser(UserDTO $userDTO): User
    {
        // 1. Criar usuário (só dados do user)
        $user = $this->createUserCommand->handle($userDTO);

        // 2. Criar profile se tiver dados
        if ($userDTO->hasProfile()) {
            $this->profileRepository->createForUser($user, $userDTO->getProfileData());
            $user->load('profile');
        }

        // 3. Audit
        $this->auditService->logCreated($user);

        // 4. Side effects
        if ($this->featureFlagService->isEnabled('user_welcome_email', Auth::id())) {
            $this->dispatchWelcomeEmail($user);
        }

        return $user;
    }


    /**
     * Get user with profile
     */
    public function getUserWithProfile(int $id): ?User
    {
        $user = $this->getUserWithProfileQuery->handle($id);

        if (!$user) return null;

        $this->auditService->log('users.viewed', 'User details viewed', ['user_id' => $user->id]);

        // ✅ Service aplica feature flag para relacionamentos extras
        $detailedProfileEnabled = $this->featureFlagService->isEnabled('user_detailed_profile', Auth::id());

        if ($detailedProfileEnabled) {
            $user->load(['roles', 'permissions']);
        }

        return $user;
    }

    /**
     * Get all users paginated
     */
    public function getAllUsersPaginated(int $perPage = 15): LengthAwarePaginator
    {
        // Feature flag no Service
        $maxPerPageEnabled = $this->featureFlagService->isEnabled('user_large_pagination', Auth::id());
        $maxPerPage = $maxPerPageEnabled ? 100 : 50;
        $perPage = min($perPage, $maxPerPage);

        $users = $this->getAllUsersQuery->handle($perPage);

        // ✅ Audit de listagem
        $this->auditService->log(
            'users.list_viewed',
            'User list viewed',
            ['per_page' => $perPage, 'total' => $users->total()],
            Auth::user(),
            0
        );

        return $users;
    }

    /**
     * Update user
     */
    public function updateUser(User $user, UserDTO $userDTO): User
    {
        // 1. Atualizar usuário
        $this->updateUserCommand->handle($user, $userDTO);

        // 2. Atualizar profile se tiver dados
        if ($userDTO->hasProfile()) {
            $this->profileRepository->updateOrCreateForUser($user, $userDTO->getProfileData());
        }

        // 3. Audit (negócio)
        $this->auditService->logUpdated($user, ['user_id' => $user->id]);

        // 4. Preparar resposta com dados frescos
        $freshUser = $user->fresh();

        // ✅ LÓGICO: Se atualizou profile, carrega para mostrar
        if ($userDTO->hasProfile()) {
            $freshUser->load('profile');
        }

        // Feature flag para dados extras
        if ($this->featureFlagService->isEnabled('user_enhanced_update_response', Auth::id())) {
            $freshUser->load(['roles', 'permissions']);
        }

        // ✅ CORREÇÃO: Retornar $freshUser em vez de $user
        return $freshUser; // ← ERA ISSO!
    }


    /**
     * Delete user
     */
    public function deleteUser(User $user): bool
    {
        $result = $this->deleteUserCommand->handle($user);

        if ($result) {
            $this->auditService->logDeleted($user, ['user_id' => $user->id]);
        }

        return $result;
    }


    protected function dispatchWelcomeEmail(User $user): void
    {
        try {
            \App\Jobs\SendWelcomeEmailJob::dispatch($user);
        } catch (Exception $e) {
            Log::warning('Failed to dispatch welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
