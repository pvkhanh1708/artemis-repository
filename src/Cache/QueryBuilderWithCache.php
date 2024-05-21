<?php

namespace Artemis\Repository\Cache;

use Cache;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Query\Builder as QueryBuilder;

class QueryBuilderWithCache extends QueryBuilder
{
    protected int $cacheTime;
    protected string $modelName;
    public array $defaultCacheTags = ['QueryCache'];

    /**
     * @param ConnectionInterface $connection
     * @param Grammar|null $grammar
     * @param Processor|null $processor
     */
    public function __construct(
        ConnectionInterface $connection,
        Grammar             $grammar = null,
        Processor           $processor = null
    )
    {
        parent::__construct($connection, $grammar, $processor);
    }

    /**
     * @param $cacheTime
     * @return $this
     */
    public function cacheFor($cacheTime): static
    {
        $this->cacheTime = $cacheTime;
        return $this;
    }

    /**
     * @param $modelName
     * @return $this
     */
    public function withName($modelName): static
    {
        $this->modelName = $modelName;
        return $this;
    }

    /**
     * @return int
     */
    public function getCacheTime(): int
    {
        return $this->cacheTime;
    }

    /**
     * @return string
     */
    public function cacheKey(): string
    {
        return md5(vsprintf('%s.%s.%s', [
            $this->toSql(),
            json_encode($this->getBindings(), true),
            !$this->useWritePdo,
        ]));
    }

    /**
     * @return mixed
     */
    protected function runSelect(): mixed
    {
        if ($this->cacheTime && app()->make('request')->tag) {
            $tags = array_unique(array_merge(
                [
                    app()->make('request')->tag,
                    $this->modelName . '_' . app()->make('request')->tag,
                    $this->cacheKey()
                ],
                $this->defaultCacheTags
            ));

            $cacheTime = $this->getCacheTime();
            $this->cacheTime = null;
            return Cache::tags($tags)->remember($this->cacheKey(), $cacheTime, function () {
                return parent::runSelect();
            });
        }

        return parent::runSelect();
    }
}
