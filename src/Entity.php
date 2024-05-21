<?php

namespace Artemis\Repository;

use Artemis\Repository\Cache\ModelCacheTrait;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model implements EntityInterface
{
    use ModelCacheTrait;

    /**
     * Acl groups allow action
     * example:
     * [
     *   'view' => ['admin', 'accountant'],
     *   'create' => ['admin', 'sale'],
     *   'update' => ['admin'],
     *   'delete' => ['admin']
     * ]
     * @var array
     */
    public static array $permissions = [];

    /**
     * @return array
     */
    public function getAllAllowColumns(): array
    {
        $timestampColumns = [];
        if ($this->usesTimestamps()) {
            $timestampColumns[] = $this->getCreatedAtColumn();
            $timestampColumns[] = $this->getUpdatedAtColumn();
        }
        return array_merge([$this->getKeyName()], $this->getFillable(), $timestampColumns);
    }

    /**
     * @param $query
     * @param $sort
     * @return mixed
     */
    public function scopeSort($query, $sort = null): mixed
    {
        if (is_null($sort)) {
            $sort = $this->usesTimestamps() ? 'created_at:-1' : 'id:-1';
        }
        $columns = $this->getAllAllowColumns();
        $sorts = explode(',', $sort);
        foreach ($sorts as $sort) {
            $sort = explode(':', $sort);
            list($field, $type) = [Arr::get($sort, '0', 'created_at'), Arr::get($sort, '1', 1)];
            if (in_array($field, $columns)) {
                $query->orderBy($this->getTable() . '.' . $field, $type == 1 ? 'ASC' : 'DESC');
            }
        }
        return $query;
    }

    /**
     * @param string|null $prefix
     * @param string $attributes
     * @return void
     */
    protected function generateCode(string $prefix = null, string $attributes = 'code'): void
    {
        $this->$attributes = Code::generate($this->id, $prefix = $prefix);
        $this->save();
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
     * @param array $includes
     * @return array
     */
    public static function lazyloadInclude(array $includes): array
    {
        $with = [];
        foreach ($includes as $include) {
            if (isset(static::$mapLazyLoadInclude[$include])) {
                $with[] = static::$mapLazyLoadInclude[$include];
            }
        }
        return $with;
    }
}
