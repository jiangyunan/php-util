<?php
/**
 * Created by PhpStorm.
 * User: jiangyunan
 * Date: 2017/8/14
 * Time: 15:29
 */

namespace DevManage\Util;


class RedisLock
{

    /** sleep时间 */
    const LOCK_DEFAULT_WAIT_SLEEP = 3000;

    protected $key;

    /**
     * @var \Redis $redis
     */
    protected $redis;

    protected $lockTime;

    public function __construct(\Redis $redis, $key, $lockTime = 5000)
    {
        $this->key = 'lock_' . $key;
        $this->redis = $redis;
        $this->lockTime = $lockTime;
    }

    function lock()
    {
        $lock = false;
        $time = microtime(true);
        $exitTime = $time + $this->lockTime;

        while(!$lock) {
            /*
             * 第一个获得锁的线程，将lockKey的值设置为当前时间+5000毫秒，
             * 后面会判断，如果5秒之后，获得锁的线程还没有执行完，会忽略之前获得锁的线程，
             * 而直接获取锁，所以这个时间需要根据自己业务的执行时间来设置长短
             *
             */
            $lock = $this->redis->setnx($this->key, $exitTime);

            if ($lock) {
                return $lock;
            }

            // 没获得锁的线程可以执行到这里：从Redis获取老的时间戳
            $oldTime = $this->redis->get($this->key);

            if ($oldTime) {
                //如果oldTimeLong小于当前时间了，说明之前持有锁的线程执行时间大于5秒了，就强制忽略该线程所持有的锁，重新设置自己的锁
                if ($oldTime < $time) {
                    //调用getset方法获取之前的时间戳,注意这里会出现多个线程竞争，但肯定只会有一个线程会拿到第一次获取到锁时设置的expireTime
                    $oldTime2 = $this->redis->getSet($this->key, microtime(true) + 5000);

                    //如果刚获取的时间戳和之前获取的时间戳一样的话,说明没有其他线程在占用这个锁,则此线程可以获取这个锁.
                    if ($oldTime2 && $oldTime2 == $oldTime) {
                        $lock = true;
                        break;
                    }
                }
            }

            usleep(self::LOCK_DEFAULT_WAIT_SLEEP);
        }

        return $lock;
    }

    function unlock()
    {
        $this->redis->del($this->key);
    }
}