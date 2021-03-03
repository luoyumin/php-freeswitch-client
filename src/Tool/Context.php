<?php
declare(strict_types=1);

namespace FreeSWITCH\Tool;

use Swoole\Coroutine;

/**
 * Class Context
 * @package FreeSWITCH\tool
 */
class Context
{
    protected static $nonCoContext = [];

    public static function set(string $id, $value)
    {
        if (Coroutine::getCid()) {
            Coroutine::getContext()[$id] = $value;
        } else {
            static::$nonCoContext[$id] = $value;
        }
        return $value;
    }

    public static function get(string $id, $default = null, $coroutineId = null)
    {
        if (Coroutine::getCid()) {
            if ($coroutineId !== null) {
                return Coroutine::getContext($coroutineId)[$id] ?? $default;
            }
            return Coroutine::getContext()[$id] ?? $default;
        }

        return static::$nonCoContext[$id] ?? $default;
    }

    public static function has(string $id, $coroutineId = null)
    {
        if (Coroutine::getCid()) {
            if ($coroutineId !== null) {
                return isset(Coroutine::getContext($coroutineId)[$id]);
            }
            return isset(Coroutine::getContext()[$id]);
        }

        return isset(static::$nonCoContext[$id]);
    }

    /**
     * @param string $id
     */
    public static function destroy(string $id)
    {
        unset(static::$nonCoContext[$id]);
    }

    /**
     * Copy the context from a coroutine to current coroutine.
     */
    public static function copy(int $fromCoroutineId, array $keys = []): void
    {
        /** @var \ArrayObject $from */
        $from = Coroutine::getContext($fromCoroutineId);
        /** @var \ArrayObject $current */
        $current = Coroutine::getContext();

        $current->exchangeArray($keys ? array_intersect_key($from->getArrayCopy(), array_flip((array)$keys)) : $from->getArrayCopy());
    }

    /**
     * Retrieve the value and override it by closure.
     */
    public static function override(string $id, \Closure $closure)
    {
        $value = null;
        if (self::has($id)) {
            $value = self::get($id);
        }
        $value = $closure($value);
        self::set($id, $value);
        return $value;
    }

    /**
     * @param string $id
     * @param $value
     * @return mixed|null
     */
    public static function getOrSet(string $id, $value)
    {
        if (!self::has($id)) {
            return self::set($id, value($value));
        }
        return self::get($id);
    }

    public static function getContainer()
    {
        if (Coroutine::getCid()) {
            return Coroutine::getContext();
        }

        return static::$nonCoContext;
    }
}