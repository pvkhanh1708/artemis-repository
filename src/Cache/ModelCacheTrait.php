<?php

namespace Artemis\Repository\Cache;

trait ModelCacheTrait
{
    const CACHE_TIME = 200;

    public function cacheTime(): int
    {
        return self::CACHE_TIME;
    }

    /**
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::observe(
            FlushCacheObserver::class
        );
    }

    /**
     * @return string
     */
    public static function getName(): string
    {
        preg_match('@\\\\([\w]+)$@', get_called_class(), $matches);
        return $matches[1];
    }

    /**
     * @param $type
     * @return array
     */
    public function listCacheKeys($type): array
    {
        return array_unique(array_merge($this->defaultCacheKeys($type), $this->customCacheKeys($type)));
    }

    /**
     * @param $type
     * @return array|string[]
     */
    public function defaultCacheKeys($type): array
    {
        switch ($type) {
            case 'detail':
                return [
                    CacheKey::generate(env('APP_NAME'), $this->getName() . '.getById', ['id' => $this->id]),
                    CacheKey::generate(env('APP_NAME'), $this->getName() . '.getByIdInTrash', ['id' => $this->id])
                ];
            case 'list':
                return [
                    'lists.' . $this->getName()
                ];
            default:
                return [];
        }
    }

    /**
     * @param $type
     * @return array
     */
    public function customCacheKeys($type): array
    {
        return [];
    }

    /**
     * @return QueryBuilderWithCache
     */
    protected function newBaseQueryBuilder(): QueryBuilderWithCache
    {
        $connection = $this->getConnection();
        $queryBuilder =
            new QueryBuilderWithCache(
                $connection,
                $connection->getQueryGrammar(),
                $connection->getPostProcessor()
            );
        return $queryBuilder->cacheFor(self::CACHE_TIME)->withName($this->getName());
    }
}
