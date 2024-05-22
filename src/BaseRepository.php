<?php

namespace Artemis\Repository;

use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Artemis\Repository\Cache\QueryCacheTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use ReflectionClass;

abstract class BaseRepository implements BaseRepositoryInterface
{
    use QueryCacheTrait;
    /**
     * Eloquent model
     * @var Model
     */
    protected Model $model;
    protected ?ReflectionClass $reflection = null;

    /**
     * @return ReflectionClass
     */
    protected function getReflection(): ReflectionClass
    {
        if (is_null($this->reflection)) {
            $this->reflection = new ReflectionClass($this->getModel());
        }
        return $this->reflection;
    }

    /**
     * @return Model
     */
    protected function getModel(): Model
    {
        return $this->model;
    }

    /**
     * @param $object
     * @return Eloquent
     */
    public function getInstance($object): Eloquent
    {
        if (is_a($object, get_class($this->getModel()))) {
            return $object;
        } else {
            return $this->getById($object);
        }
    }

    /**
     * @param array $params
     * @param int $size
     * @return Illuminate\Pagination\Paginator
     */
    public function getByQuery(array $params = [], int $size = 25): Illuminate\Pagination\Paginator
    {
        $sort = Arr::get($params, 'sort', 'created_at:-1');

        $params['sort'] = $sort;
        $lModel = $this->getModel();
        $query = Arr::except($params, ['page', 'limit']);
        if (count($query)) {
            $lModel = $this->applyFilterScope($lModel, $query);
        }

        $callback = match ($size) {
            -1 => function ($query, $size) {
                return $query->get();
            },
            0 => function ($query, $size) {
                return $query->first();
            },
            default => function ($query, $size) {
                return $query->paginate($size);
            },
        };
        $records =  $this->callWithCache(
            $callback,
            [$lModel, $size],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getKeyName() . '.getByQuery', Arr::dot($params)),
            $this->getModel()->defaultCacheKeys('list')
        );
        return $this->lazyLoadInclude($records);
    }

    /**
     * @param $lModel
     * @param array $params
     * @return mixed
     */
    protected function applyFilterScope($lModel, array $params): mixed
    {
        foreach ($params as $funcName => $funcParams) {
            $funcName = Str::studly($funcName);
            if ($this->getReflection()->hasMethod('scope' . $funcName)) {
                $funcName = lcfirst($funcName);
                $lModel = $lModel->$funcName($funcParams);
            }
        }
        return $lModel;
    }

    /**
     * @return array
     */
    protected function getIncludes(): array
    {
        $query = app()->make(Request::class)->query();
        $includes = Arr::get($query, 'include', []);
        if (!is_array($includes)) {
            $includes = array_map('trim', explode(',', $includes));
        }
        return $includes;
    }

    /**
     * @param $objects
     * @return mixed
     */
    protected function lazyLoadInclude($objects): mixed
    {
        if ($this->getReflection()->hasProperty('mapLazyLoadInclude')) {
            $includes = $this->getIncludes();
            $with = call_user_func($this->getReflection()->name . '::lazyloadInclude', $includes);
            if (get_class($objects) == LengthAwarePaginator::class) {
                return $objects->setCollection($objects->load($with));
            }
            return $objects->load($with);
        }
        return $objects;
    }

    /**
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function getById(int $id, string $field = 'id'): mixed
    {
        $callback = function ($id, $static, $key) {
            if ($key != $static->getModel()->getKeyName()) {
                return $static->getModel()->where($key, $id)->firstOrFail();
            }
            return $static->getModel()->findOrFail($id);
        };
        $record =  $this->callWithCache(
            $callback,
            [$id, $this, $field],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getKeyName() . '.getById', [$field => $id])
        );

        return $this->lazyLoadInclude($record);
    }

    /**
     * @param int $id
     * @param string $field
     * @return mixed
     */
    public function getByIdInTrash(int $id, string $field = 'id'): mixed
    {
        $callback = function ($id, $static, $key) {
            if ($key != $static->getModel()->getKeyName()) {
                return $static->getModel()->withTrashed()->where($key, $id)->firstOrFail();
            }
            return $static->getModel()->withTrashed()->findOrFail($id);
        };
        $record = $this->callWithCache(
            $callback,
            [$id, $this, $field],
            $this->getCacheKey(env('APP_NAME'), $this->getModel()->getKeyName() . '.getByIdInTrash', [$field => $id])
        );
        return $this->lazyLoadInclude($record);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public function store(array $data): mixed
    {
        return $this->getModel()->create(Arr::only($data, $this->getModel()->getFillable()));
    }

    /**
     * @param array $datas
     * @return mixed
     */
    public function storeArray(array $datas): mixed
    {
        if (count($datas) && is_array(reset($datas))) {
            $fillable = $this->getModel()->getFillable();
            $now = Carbon::now();

            foreach ($datas as $key => $data) {
                $datas[$key] = Arr::only($data, $fillable);
                if ($this->getModel()->usesTimestamps()) {
                    $datas[$key]['created_at'] = $now;
                    $datas[$key]['updated_at'] = $now;
                }
            }
            $result = $this->getModel()->insert($datas);
            if ($result) {
                Cache::tags($this->getModel()->listCacheKeys('list'))->flush();
            }
            return $result;
        }

        return $this->store($datas);
    }

    /**
     * @param int $id
     * @param array $data
     * @param array $excepts
     * @param array $only
     * @return Eloquent
     */
    public function update(int $id, array $data, array $excepts = [], array $only = []): Eloquent
    {
        $data = Arr::except($data, $excepts);
        if (count($only)) {
            $data = Arr::only($data, $only);
        }
        $record = $this->getInstance($id);

        $record->fill($data)->save();
        return $record;
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function delete(int $id): mixed
    {
        $record = $this->getInstance($id);
        return $record->delete();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function destroy(int $id): mixed
    {
        $record = $this->getInstance($id);

        return $record->forceDelete();
    }

    /**
     * @param int $id
     * @return mixed
     */
    public function restore(int $id): mixed
    {
        $record = $this->getInstance($id);
        return $record->restore();
    }
}
