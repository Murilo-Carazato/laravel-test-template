<?php

namespace App\Repositories\Interfaces;

use App\Models\Audit;
use Illuminate\Pagination\LengthAwarePaginator;

interface AuditRepositoryInterface
{
    /**
     * Obtém um registro de auditoria pelo ID.
     *
     * @param int $id
     * @return Audit|null
     */
    public function findById(int $id): ?Audit;
    
    /**
     * Cria um novo registro de auditoria.
     *
     * @param array $data
     * @return Audit
     */
    public function create(array $data): Audit;
    
    /**
     * Obtém registros de auditoria com paginação.
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Obtém registros de auditoria filtrados por usuário.
     *
     * @param int $userId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator;
    
    /**
     * Obtém registros de auditoria filtrados por tipo de ação.
     *
     * @param string $action
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByAction(string $action, int $perPage = 15): LengthAwarePaginator;
}
