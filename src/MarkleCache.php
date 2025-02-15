<?php

/**
 * installation:
 *
 * sudo apt install memcached
 * sudo apt install php-memcache
 */
class MarkleCacheImpl
{
    public $store;

    public function __construct() {
        $this->store = new Memcache;
        $this->store->connect('localhost', 11211) or die ("Could not connect");
    }

    public function remember($key, $seconds, Closure $callback) {
        $value = $this->get($key);

        // If the item exists in the cache we will just return this immediately and if
        // not we will execute the given Closure and cache the result of that for a
        // given number of seconds so it's available for all subsequent requests.
        if (!is_null($value)) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $seconds);

        return $value;
    }

    public function itemKey($key) {
        return $key;
    }

    public function get($key, $default = null): mixed {
        $flags = false;
        $value = $this->store->get($this->itemKey($key), $flags);

        // If we could not find the cache value, we will fire the missed event and get
        // the default value for this cache value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if ($flags === false) {
            return $default;
        }

        return $value;
    }

    public function forget($key) {
        return $this->store->delete($key);
    }

    public function has($key) {
        return !is_null($this->get($key));
    }


    public function put($key, $value, $seconds = null) {
        if ($seconds === null) {
            $seconds = 0;
        }

        if ($seconds < 0) {
            return $this->forget($key);
        }

        return $this->store->set($this->itemKey($key), $value, false, $seconds);
    }
}

class MarkleCache
{
    /**
     * @var null|MarkleCacheImpl
     */
    protected static $instance;

    /**
     * Get always the same instance within the current request
     *
     * @return static
     */
    public static function global(): MarkleCacheImpl
    {
        if (!self::$instance) {
            self::$instance = new MarkleCacheImpl;
        }

        return self::$instance;
    }

    /**
     * @param string $key unique key
     * @param int $seconds time to live in cache
     * @param callable $callback function to supply value if not in cache
     * @return mixed
     */
    public static function remember($key, $seconds, callable $callback) {
        return static::global()->remember($key, $seconds, $callback);
    }

    public static function get($key, $default = null): mixed {
        return static::global()->get($key, $default);
    }

    public static function forget($key) {
        return static::global()->forget($key);
    }

    public static function has($key) {
        return static::global()->has($key);
    }

    public static function put($key, $value, $seconds = null) {
        return static::global()->put($key, $value, $seconds);
    }
}

function markle_cache_test() {
    MarkleCache::put('Mark', 'Dubious Fellow', 60);
    if (MarkleCache::has('Mark')) {
        printf("Ok, Mark is a %s\n", MarkleCache::get('Mark', 'Unknown'));
        MarkleCache::forget('Mark');
    }

    printf("Mark is a %s\n",  MarkleCache::remember('Mark',  60, function ()  { return "WTF"; }));
    printf("Cyrus is a %s\n", MarkleCache::remember('Cyrus', 60, function ()  { return "Lexus"; }));
    printf("Mark is a %s\n",  MarkleCache::remember('Mark',  60, function ()  { return "Trueno"; }));
    printf("Done!\n");
}

// markle_cache_test();