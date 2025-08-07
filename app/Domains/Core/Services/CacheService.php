<?php

namespace App\Domains\Core\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

class CacheService
{
    /**
     * Tempo padrão de cache em minutos
     */
    protected int $defaultTtl = 60;

    /**
     * Prefixo para as chaves de cache
     */
    protected string $prefix;

    /**
     * Construtor do serviço de cache
     */
    public function __construct(string $prefix = 'app_cache')
    {
        $this->prefix = $prefix;
    }

    /**
     * Obtém um item do cache ou executa o callback se não existir
     */
    public function remember(string $key, callable $callback, ?int $ttl = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::remember($cacheKey, $ttl * 60, $callback);
    }

    /**
     * Armazena um valor no cache
     */
    public function put(string $key, mixed $value, ?int $ttl = null): bool
    {
        $cacheKey = $this->getCacheKey($key);
        $ttl = $ttl ?? $this->defaultTtl;

        return Cache::put($cacheKey, $value, $ttl * 60);
    }

    /**
     * Verifica se um item existe no cache
     */
    public function has(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::has($cacheKey);
    }

    /**
     * Obtém um item do cache
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::get($cacheKey, $default);
    }

    /**
     * Remove um item do cache
     */
    public function forget(string $key): bool
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::forget($cacheKey);
    }

    /**
     * Limpa o cache com base em um padrão de tag/prefixo
     */
    public function flush(string $pattern): bool
    {
        if (config('cache.default') === 'redis') {
            return $this->flushRedisPattern("{$this->prefix}:{$pattern}*");
        }

        // Para outros drivers, devemos usar uma abordagem diferente
        return Cache::flush();
    }

    /**
     * Limpa cache Redis com base em um padrão
     */
    protected function flushRedisPattern(string $pattern): bool
    {
        $redis = Cache::getRedis();
        $keys = $redis->keys($pattern);
        
        if (!empty($keys)) {
            $redis->del($keys);
        }
        
        return true;
    }

    /**
     * Gera uma chave de cache para um modelo
     */
    public function generateModelKey(Model $model): string
    {
        return get_class($model) . ':' . $model->getKey();
    }

    /**
     * Gera chave de cache para uma coleção de modelos
     */
    public function generateCollectionKey(Collection $collection, string $identifier = 'collection'): string
    {
        if ($collection->isEmpty()) {
            return class_basename($collection) . ':empty:' . $identifier;
        }

        $modelClass = get_class($collection->first());
        $ids = $collection->pluck($collection->first()->getKeyName())->sort()->implode(',');
        
        return $modelClass . ':' . $identifier . ':' . md5($ids);
    }

    /**
     * Formata uma chave de cache com o prefixo
     */
    protected function getCacheKey(string $key): string
    {
        return "{$this->prefix}:{$key}";
    }

    /**
     * Define o TTL padrão
     */
    public function setDefaultTtl(int $minutes): self
    {
        $this->defaultTtl = $minutes;
        return $this;
    }
}