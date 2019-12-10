<?php

namespace Codeages\Library;

/**
 * 字符串的工具类
 *
 * @package Codeages\Library
 */
class Str
{
    /**
     * @param $length
     * @return string
     * @throws \Exception
     */
    public static function random($length)
    {
        $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[random_int(0, $max)];
        }
        return $str;
    }
}