<?php

namespace Artemis\Repository\Cache;

use Illuminate\Support\Str;

class CacheKey
{
    public static function generate($service, $function, array $bindings): string
    {
        return md5(vsprintf('%s.%s.%s', [
            Str::snake($service),
            Str::snake($function),
            json_encode($bindings, true)
        ]));
    }
}
