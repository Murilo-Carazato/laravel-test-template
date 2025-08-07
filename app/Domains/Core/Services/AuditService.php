<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Interfaces\AuditRepositoryInterface;

class AuditService
{
    /**
     * Tipos de eventos de segurança
     */
    const EVENT_LOGIN_SUCCESS = 'login_success';
    const EVENT_LOGIN_FAILURE = 'login_failure';
    const EVENT_PERMISSION_DENIED = 'permission_denied';
    const EVENT_SENSITIVE_DATA_ACCESS = 'sensitive_data_access';
    const EVENT_ACCOUNT_CHANGE = 'account_change';
    const EVENT_API_KEY_USAGE = 'api_key_usage';
    const EVENT_SUSPICIOUS_ACTIVITY = 'suspicious_activity';

    /**
     * @var AuditRepositoryInterface
     */
    protected $auditRepository;
    
    /**
     * Constructor
     * 
     * @param AuditRepositoryInterface $auditRepository
     */
    public function __construct(AuditRepositoryInterface $auditRepository)
    {
        $this->auditRepository = $auditRepository;
    }
    
    /**
     * Registra uma ação de auditoria
     *
     * @param string $action A ação realizada
     * @param string $entity Entidade relacionada à ação
     * @param array $data Dados associados à ação
     * @param Model|null $model Modelo associado à ação
     * @return \App\Models\Audit
     */
    public function log(string $action, string $entity, array $data = [], ?Model $model = null)
    {
        $userId = Auth::id();
        
        $auditData = [
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
        
        if ($model) {
            $auditData['model_type'] = get_class($model);
            $auditData['model_id'] = $model->getKey();
        }
        
        return $this->auditRepository->create($auditData);
    }
    
    /**
     * Registra uma ação de criação
     *
     * @param Model $model Modelo criado
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logCreated(Model $model, array $data = [])
    {
        $entity = class_basename($model);
        return $this->log(
            'created',
            $entity,
            array_merge(['attributes' => $model->getAttributes()], $data),
            $model
        );
    }
    
    /**
     * Registra uma ação de atualização
     *
     * @param Model $model Modelo atualizado
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logUpdated(Model $model, array $data = [])
    {
        $entity = class_basename($model);
        $dirty = $model->getDirty();
        $original = array_intersect_key($model->getOriginal(), $dirty);
        
        return $this->log(
            'updated',
            $entity,
            array_merge([
                'attributes' => $dirty,
                'original' => $original,
            ], $data),
            $model
        );
    }
    
    /**
     * Registra uma ação de exclusão
     *
     * @param Model $model Modelo excluído
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logDeleted(Model $model, array $data = [])
    {
        $entity = class_basename($model);
        return $this->log(
            'deleted',
            $entity,
            array_merge(['attributes' => $model->getAttributes()], $data),
            $model
        );
    }
    
    /**
     * Registra uma ação de login
     *
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logLogin(array $data = [])
    {
        return $this->log('login', 'Auth', $data);
    }
    
    /**
     * Registra uma ação de logout
     *
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logLogout(array $data = [])
    {
        return $this->log('logout', 'Auth', $data);
    }
    
    /**
     * Registra uma ação de falha de login
     *
     * @param string $username Nome de usuário que falhou
     * @param array $data Dados adicionais
     * @return \App\Models\Audit
     */
    public function logFailedLogin(string $username, array $data = [])
    {
        return $this->log(
            'failed_login',
            'Auth',
            array_merge(['username' => $username], $data)
        );
    }
    
    /**
     * Registra um evento de segurança
     *
     * @param string $eventType Tipo do evento de segurança
     * @param array $details Detalhes adicionais do evento
     * @param int|null $userId ID do usuário envolvido (null se não autenticado)
     * @return void
     */
    public function logSecurityEvent(string $eventType, array $details = [], ?int $userId = null): void
    {
        $userId = $userId ?? (Auth::check() ? Auth::id() : null);
        
        $eventData = [
            'event_type' => $eventType,
            'user_id' => $userId,
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
            'request_path' => Request::path(),
            'request_method' => Request::method(),
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Log detalhado para análise de segurança
        Log::channel('security')->info('Security event: ' . $eventType, $eventData);
        
        // Criar registro de auditoria
        $this->log(
            'security_' . $eventType,
            'Security',
            $eventData
        );
    }
    
    /**
     * Detecta atividades potencialmente suspeitas
     *
     * @param array $context Contexto da atividade a ser verificada
     * @return bool Se a atividade é considerada suspeita
     */
    public function detectSuspiciousActivity(array $context): bool
    {
        // Implementar lógicas de detecção de anomalias
        // Exemplos:
        // - Múltiplas falhas de login
        // - Acesso de localização incomum
        // - Padrões de acesso anormais
        // - Volume incomum de solicitações
        
        return false; // Implementação simplificada
    }
    
    /**
     * Verifica se o usuário atual tem permissão para ação específica
     * e registra tentativas não autorizadas
     *
     * @param string $action Ação sendo executada
     * @param mixed $resource Recurso sendo acessado
     * @return bool
     */
    public function checkAndLogAccess(string $action, $resource): bool
    {
        $user = Auth::user();
        
        if (!$user || !$user->can($action, $resource)) {
            $this->logSecurityEvent(self::EVENT_PERMISSION_DENIED, [
                'action' => $action,
                'resource_type' => is_object($resource) ? get_class($resource) : gettype($resource),
                'resource_id' => is_object($resource) && method_exists($resource, 'getKey') ? $resource->getKey() : null,
            ]);
            return false;
        }
        
        return true;
    }
}