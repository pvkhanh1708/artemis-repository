<?php

namespace Artemis\Repository;


interface BaseRepositoryInterface
{
    public function getByQuery(array $params = [], int $size = 25);

    public function getById(int $id, string $field = 'id');

    public function getByIdInTrash(int $id, string $field = 'id');

    public function store(array $data);

    public function storeArray(array $datas);

    public function update(int $id, array $data, array $excepts = [], array $only = []);

    public function delete(int $id);

    public function destroy(int $id);

    public function restore(int $id);
}
