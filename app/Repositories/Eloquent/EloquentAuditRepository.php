<?php

namespace App\Repositories\Eloquent;

use App\Models\Audit;
use App\Repositories\Interfaces\AuditRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class EloquentAuditRepository implements AuditRepositoryInterface
{
    /**
     * @var Audit
     */
    protected $model;

    /**
     * EloquentAuditRepository constructor.
     *
     * @param Audit $audit
     */
    public function __construct(Audit $audit)
    {
        $this->model = $audit;
    }

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Audit
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Audit
    {
        return $this->model->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByAction(string $action, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->where('action', $action)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
