<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Feature;
use DateTimeInterface;

class FeatureFlagService
{
    /**
     * Cache TTL in minutes
     * 
     * @var int
     */
    protected $cacheTtl = 60;

    /**
     * Get all feature flags
     *
     * @return Collection
     */
    public function getAllFeatures(): Collection
    {
        return Cache::remember('features.all', $this->cacheTtl, function () {
            return Feature::all();
        });
    }

    /**
     * Check if a feature is enabled
     *
     * @param string $featureName
     * @param int|null $userId
     * @return bool
     */
    public function isEnabled(string $featureName, ?int $userId = null): bool
    {
        // Check for specific user override
        if ($userId) {
            $userOverride = $this->getUserFeatureStatus($featureName, $userId);
            if ($userOverride !== null) {
                return $userOverride;
            }
        }

        // Check global feature status
        return Cache::remember("feature.{$featureName}", $this->cacheTtl, function () use ($featureName) {
            $feature = Feature::where('name', $featureName)->first();
            return $feature ? $feature->enabled : false;
        });
    }

    /**
     * ✅ ADICIONADO: Alias para o método isEnabled (compatibilidade com controller)
     */
    public function isActive(string $featureName, ?int $userId = null): bool
    {
        return $this->isEnabled($featureName, $userId);
    }
    
    /**
     * Cria ou atualiza uma feature flag.
     *
     * @param string $name
     * @param bool $enabled
     * @param int $percentage (não usado por enquanto, mas mantido para compatibilidade)
     * @param DateTimeInterface|null $expiresAt
     * @return bool
     */
    public function createOrUpdate(string $name, bool $enabled, int $percentage = 0, ?DateTimeInterface $expiresAt = null): bool
    {
        try {
            $feature = Feature::firstOrNew(['name' => $name]);
            $feature->enabled = $enabled;
            $feature->description = $feature->description ?? "Feature: {$name}";
            // Note: expires_at não está na migration atual, removendo por enquanto
            $feature->save();

            Cache::forget("feature.{$name}");
            Cache::forget('features.all');

            return true;
        } catch (\Exception $e) {
            Log::error("Error creating/updating feature {$name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtém o status da feature para um usuário específico.
     *
     * @param string $featureName
     * @param int $userId
     * @return bool|null Retorna true/false se houver override, null caso contrário.
     */
    protected function getUserFeatureStatus(string $featureName, int $userId): ?bool
    {
        return Cache::remember("feature.{$featureName}.user.{$userId}", $this->cacheTtl, function () use ($featureName, $userId) {
            $feature = Feature::where('name', $featureName)->first();
            if (!$feature) {
                return null; // Feature não existe
            }
            $userSetting = $feature->users()->where('user_id', $userId)->first();
            return $userSetting ? (bool) $userSetting->pivot->enabled : null;
        });
    }

    /**
     * Ativa uma feature flag para um usuário específico.
     *
     * @param string $featureName
     * @param int $userId
     * @return bool
     */
    public function enableForUser(string $featureName, int $userId): bool
    {
        try {
            $feature = Feature::where('name', $featureName)->first();
            if (!$feature) {
                // ✅ MELHORADO: Criar feature se não existir
                $feature = Feature::create([
                    'name' => $featureName,
                    'enabled' => false,
                    'description' => "Auto-created feature: {$featureName}"
                ]);
            }

            $feature->users()->syncWithoutDetaching([$userId => ['enabled' => true]]);
            Cache::forget("feature.{$featureName}.user.{$userId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error enabling feature {$featureName} for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Desativa uma feature flag para um usuário específico.
     *
     * @param string $featureName
     * @param int $userId
     * @return bool
     */
    public function disableForUser(string $featureName, int $userId): bool
    {
        try {
            $feature = Feature::where('name', $featureName)->first();
            if (!$feature) {
                return false;
            }

            $feature->users()->syncWithoutDetaching([$userId => ['enabled' => false]]);
            Cache::forget("feature.{$featureName}.user.{$userId}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error disabling feature {$featureName} for user {$userId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Lista todas as feature flags (método para o controller).
     *
     * @return Collection
     */
    public function listAll(): Collection
    {
        return $this->getAllFeatures();
    }

    /**
     * ✅ ADICIONADO: Obter features de um usuário específico
     */
    public function getUserFeatures(int $userId): Collection
    {
        return Cache::remember("user.{$userId}.features", $this->cacheTtl, function () use ($userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) {
                return collect();
            }

            return $user->features()->get(['feature_name as name', 'enabled']);
        });
    }
}