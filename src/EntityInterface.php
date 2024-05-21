<?php

namespace Artemis\Repository;

interface EntityInterface
{
    /**
     * @param $query
     * @param $sort
     * @return mixed
     */
    public function scopeSort($query, $sort = null): mixed;
}
