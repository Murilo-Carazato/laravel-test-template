<?php

namespace App\Repositories\Eloquent;

use App\Models\Profile;
use App\Models\User;
use App\Repositories\Interfaces\ProfileRepositoryInterface;

class EloquentProfileRepository implements ProfileRepositoryInterface
{
    /**
     * @var Profile
     */
    protected $model;

    /**
     * EloquentProfileRepository constructor.
     *
     * @param Profile $profile
     */
    public function __construct(Profile $profile)
    {
        $this->model = $profile;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Profile
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUserId(int $userId): ?Profile
    {
        return $this->model->where('user_id', $userId)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Profile
    {
        return $this->model->create($data);
    }

    /**
     * ✅ NOVO: Cria perfil para usuário específico
     * {@inheritdoc}
     */
    public function createForUser(User $user, array $data): Profile
    {
        // Adiciona user_id automaticamente
        $data['user_id'] = $user->id;
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Profile $profile, array $data): bool
    {
        return $profile->update($data);
    }

    /**
     * ✅ NOVO: Atualiza ou cria perfil para usuário
     * {@inheritdoc}
     */
    public function updateOrCreateForUser(User $user, array $data): Profile
    {
        $existingProfile = $this->findByUserId($user->id);
        
        if ($existingProfile) {
            $existingProfile->update($data);
            return $existingProfile;
        }
        
        return $this->createForUser($user, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Profile $profile): bool
    {
        return $profile->delete();
    }
}