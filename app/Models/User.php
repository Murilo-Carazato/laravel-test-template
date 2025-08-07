<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles; // ✅ ADICIONAR se usar Spatie

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    // use HasRoles; // ✅ Descomente se usar Spatie Permission

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'device_token', // Para notificações push
        // ✅ CONSIDERAR ADICIONAR:
        // 'status', // active, inactive, suspended
        // 'email_verified_at', // Se quiser permitir definir manualmente
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_token', // ✅ ADICIONAR - sensível
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        // ✅ ADICIONAR se tiver campo status:
        // 'status' => 'string',
    ];

    /**
     * ✅ CORRETO: Get the user's profile.
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * ✅ CORRETO: Feature flags relationship
     */
    public function features()
    {
        return $this->belongsToMany(Feature::class, 'feature_user', 'user_id', 'feature_name')
            ->withPivot('enabled')
            ->withTimestamps()
            ->using(FeatureUser::class);
    }

    /**
     * ✅ ADICIONAR: Relacionamentos para feature flags detalhadas
     * (Se UserService::getUserWithProfile carrega roles/permissions)
     */
    public function roles()
    {
        // Se usar Spatie Permission:
        // return $this->belongsToMany(\Spatie\Permission\Models\Role::class);
        
        // Se usar sistema próprio:
        // return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function permissions()
    {
        // Se usar Spatie Permission:
        // return $this->belongsToMany(\Spatie\Permission\Models\Permission::class);
        
        // Se usar sistema próprio (descomente quando criar o modelo Permission):
        // return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * ✅ ADICIONAR: Relacionamento com audits (se usar)
     */
    public function audits()
    {
        return $this->hasMany(Audit::class, 'user_id');
    }

    /**
     * ✅ ADICIONAR: Relacionamento polimórfico com audits (se for auditável)
     */
    public function auditLogs()
    {
        return $this->morphMany(Audit::class, 'auditable');
    }

    /**
     * ✅ ADICIONAR: Scopes úteis
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * ✅ ADICIONAR: Accessor para status
     */
    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getIsVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * ✅ ADICIONAR: Método para verificar feature flags
     * (Facilita uso no código)
     */
    public function hasFeature(string $featureName): bool
    {
        return $this->features()
            ->where('feature_name', $featureName)
            ->where('enabled', true)
            ->exists();
    }
}