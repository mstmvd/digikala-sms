<?php
/**
 * Created by PhpStorm.
 * User: mostafa
 * Date: 7/20/19
 * Time: 12:47 PM
 */

namespace App\Facade;

use Symfony\Component\Cache\Adapter\RedisAdapter;

class Cache
{
    public static function get($key, $callback = null)
    {
        if ($callback == null) {
            $callback = function () {
            };
        }
        $cache = new RedisAdapter(RedisAdapter::createConnection($_ENV['REDIS_DNS']));
        return $cache->get($key, $callback);
    }
}