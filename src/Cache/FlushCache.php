<?php

namespace Artemis\Repository\Cache;

use Cache;

class FlushCache
{
    public static function all(): void
    {
        Cache::tags('QueryCache')->flush();
    }


    public static function request($request): void
    {
        Cache::tags($request->tag)->flush();
    }
}
