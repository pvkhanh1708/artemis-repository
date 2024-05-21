<?php
namespace Artemis\Repository;

class Code
{
    /**
     * @param $code
     * @param $prefix
     * @param $length
     * @param $str
     * @return string
     */
    public static function generate($code, $prefix = null, $length = 6, $str = '0'): string
    {
        return  ($prefix ? $prefix : '') . str_pad($code, $length, $str, STR_PAD_LEFT);
    }
}
