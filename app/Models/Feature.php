<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Feature extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'enabled',
        'description',
    ];

    /**
     * Os atributos que devem ser convertidos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * Os usuários que têm configurações específicas para esta feature.
     * 
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'feature_user', 'feature_name', 'user_id')
            ->withPivot('enabled')
            ->withTimestamps()
            ->using(FeatureUser::class);
    }

    /**
     * Verifica se a feature está ativa para um usuário específico.
     *
     * @param User|int|null $user O usuário ou ID do usuário
     * @return bool
     */
    public function isEnabledForUser($user = null)
    {
        // Se não houver usuário, usa apenas a configuração global
        if (!$user) {
            return $this->enabled;
        }

        $userId = $user instanceof User ? $user->id : $user;

        // Verifica se há uma configuração específica para este usuário
        $userSetting = $this->users()->where('user_id', $userId)->first();

        if ($userSetting) {
            return (bool) $userSetting->pivot->enabled;
        }

        // Caso não haja configuração específica, usa a configuração global
        return $this->enabled;
    }
}
