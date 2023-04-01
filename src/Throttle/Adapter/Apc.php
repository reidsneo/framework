<?php

namespace Neko\Framework\Throttle\Adapter;
use Neko\Cache\Driver\File;

class Apc extends \Neko\Framework\Throttle\Adapter
{
    
    public function __construct()
    {
        $this->cache = new File([
            'storage' => app()->path.DS."app".DS."storage".DS."cache_throttle".DS
        ]);
    }
    
    public function set($key, $value, $ttl)
    {
        return $this->cache->set($key, (string) $value,$ttl);
    }

    /**
     * @return float
     */
    public function get($key)
    {
        return (float)$this->cache->get($key);
    }

    public function exists($key)
    {
        return (bool)$this->cache->has($key);
    }

    public function del($key)
    {
        return (bool)$this->cache->delete([$key]);
    }
}