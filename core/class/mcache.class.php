<?php
# vim:syntax=php ts=4 sts=4 sr ai noet fileencoding=utf-8

# for memcached cache
/**
 * Summary of MCache
 */
class MCache
{
    private $token;
    private $mc;
    public function __construct($token)
    {
        $this->token = $token;
        $this->mc = new Memcached;
        $this->mc->addServer('127.0.0.1', 11211);
    }

    /**
     * 注销或踢出token
     * @return void
     */
    public function clear(string $cachetype, string $cachekey) {
        $this->mc->delete("{$cachetype}-{$cachekey}");
    }

    /**
     * Summary of get
     * @param string $cachetype
     * @param string $cachekey
     * @return false|object
     */
    public function get(string $cachetype, string $cachekey): false|object
    {
        $cv =  $this->mc->get("{$cachetype}-{$cachekey}");
        return $cv? unserialize($cv) : false;
    }
    /**
     * Summary of set
     * @param string $cachetype
     * @param string $cachekey
     * @param object $cacheobj
     * @param int $ttl
     * @return void
     */
    public function set(string $cachetype, string $cachekey, object $cacheobj, int $ttl = 0)
    {
        $cacheval = serialize($cacheobj);
        $this->mc->set("{$cachetype}-{$cachekey}", $cacheval, $ttl);
    }
}
