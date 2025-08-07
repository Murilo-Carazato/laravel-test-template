<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\DTO\UserDTO;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class EloquentUserRepository implements UserRepositoryInterface
{
    /**
     * @var User
     */
    protected $model;

    /**
     * EloquentUserRepository constructor.
     *
     * @param User $model
     */
    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * Encontra um usuário pelo ID.
     *
     * @param int $id
     * @return User|null
     */
    public function findById(int $id): ?User
    {
        return $this->model->find($id);
    }

    /**
     * Encontra um usuário pelo email.
     *
     * @param string $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }


    /**
     * Cria um novo usuário a partir do DTO.
     *
     * @param UserDTO $userDTO
     * @return User
     */
    public function createFromDTO(UserDTO $userDTO): User
    {
        $userData = $userDTO->toArray();

        // Garantir que a senha seja hashed se estiver presente no DTO
        if (isset($userData['password'])) {
            $userData['password'] = Hash::make($userData['password']);
        }

        return $this->model->create($userData);
    }

    /**
     * {@inheritdoc}
     */
    public function updateFromDTO(User $user, UserDTO $userDTO): bool
    {
        $userData = $userDTO->toArray();

        if (isset($userData['password']) && $userData['password']) {
            $userData['password'] = Hash::make($userData['password']);
        } else {
            unset($userData['password']);
        }

        // ✅ Repository só atualiza USER
        return $user->update($userData);
    }

    /**
     * Exclui um usuário.
     *
     * @param User $user
     * @return bool
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Retorna usuários paginados.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->paginate($perPage);
    }

    /**
     * Retorna todos os usuários.
     *
     * @return Collection
     */
    public function getAll(int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }

        if (isset($filters['email'])) {
            $query->where('email', $filters['email']);
        }

        return $query->paginate($perPage);
    }



    /**
     * Cria um novo usuário.
     *
     * @param array $data
     * @return User
     */
    // public function create(array $data): User
    // {
    //     // Garante que a senha seja hash
    //     if (isset($data['password'])) {
    //         $data['password'] = Hash::make($data['password']);
    //     }

    //     return $this->model->create($data);
    // }


    /**
     * Atualiza um usuário existente.
     *
     * @param User $user
     * @param array $data
     * @return bool
     */
    // public function update(User $user, array $data): bool
    // {
    //     // Garante que a senha seja hash se fornecida
    //     if (isset($data['password'])) {
    //         $data['password'] = Hash::make($data['password']);
    //     }

    //     return $user->update($data);
    // }
}
