<?php

namespace Artemis\Repository\Cache;

use Illuminate\Database\Eloquent\Model;
use Cache;

class FlushCacheObserver
{
    /**
     * Handle the Model "created" event.
     *
     * @param Model $model
     * @return void
     */
    public function created(Model $model): void
    {
        Cache::tags($model->listCacheKeys('list'))->flush();
        Cache::tags($model->getName() . '_' . app()->make('request')->tag)->flush();
    }

    /**
     * Handle the Model "updated" event.
     *
     * @param Model $model
     * @return void
     */
    public function updated(Model $model): void
    {
        Cache::tags($model->listCacheKeys('detail'))->flush();
        Cache::tags($model->listCacheKeys('list'))->flush();
        Cache::tags($model->getName() . '_' . app()->make('request')->tag)->flush();
    }

    /**
     * Handle the Model "deleted" event.
     *
     * @param Model $model
     * @return void
     */
    public function deleted(Model $model): void
    {
        Cache::tags($model->listCacheKeys('detail'))->flush();
        Cache::tags($model->listCacheKeys('list'))->flush();
        Cache::tags($model->getName() . '_' . app()->make('request')->tag)->flush();
    }

    /**
     * Handle the Model "forceDeleted" event.
     *
     * @param Model $model
     * @return void
     */
    public function forceDeleted(Model $model): void
    {
        Cache::tags($model->listCacheKeys('detail'))->flush();
        Cache::tags($model->listCacheKeys('list'))->flush();
        Cache::tags($model->getName() . '_' . app()->make('request')->tag)->flush();
    }

    /**
     * Handle the Model "restored" event.
     *
     * @param Model $model
     * @return void
     */
    public function restored(Model $model): void
    {
        Cache::tags($model->listCacheKeys('detail'))->flush();
        Cache::tags($model->listCacheKeys('list'))->flush();
        Cache::tags($model->getName() . '_' . app()->make('request')->tag)->flush();
    }
}
