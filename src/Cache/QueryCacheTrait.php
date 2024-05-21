<?php

namespace Artemis\Repository\Cache;

trait QueryCacheTrait
{
    public bool $avoidCache = true;
    public array $defaultCacheTags = ['QueryCache'];

    /**
     * @param int $seconds
     * @return $this
     */
    public function cacheFor(int $seconds): static
    {
        $this->cacheTime = $seconds;
        $this->avoidCache = false;
        return $this;
    }

    /**
     * @return $this
     */
    public function noCache(): static
    {
        $this->avoidCache = true;
        return $this;
    }

    /**
     * @return int
     */
    public function getCacheTime(): int
    {
        return property_exists($this, 'cacheTime') ? $this->cacheTime : 0;
    }

    /**
     * @return bool
     */
    public function getInCache(): bool
    {
        return $this->getCacheTime() && !$this->avoidCache;
    }

    /**
     * @param $service
     * @param $function
     * @param array $bindings
     * @return string
     */
    public function getCacheKey($service, $function, array $bindings): string
    {
        return CacheKey::generate($service, $function, $bindings);
    }

    /**
     * @param callable $callback
     * @param array $params
     * @param $cacheKey
     * @param array $tags
     * @return mixed
     */
    public function callWithCache(callable $callback, array $params, $cacheKey, array $tags = []): mixed
    {
        $this->noCache();

        $tags = array_unique(array_merge($tags, $this->defaultCacheTags, [$cacheKey, app()->make('request')->tag]));
        if ($this->getInCache()) {
            $this->avoidCache = true;
            return \Cache::tags($tags)->remember($cacheKey, $this->getCacheTime(), function () use ($callback, $params) {
                return call_user_func_array($callback, $params);
            });
        }
        return call_user_func_array($callback, $params);
    }
}
