<?php

namespace BestIt\CTAsyncPool;

/**
 * Helps with providing the pool of async requests.
 * @author blange <code@b3nl.de>
 * @package BestIt\CTAsyncPool
 * @version $id$
 */
trait PoolAwareTrait
{
    /**
     * The pool of async requests.
     * @var PoolInterface|void
     */
    private $pool = null;

    /**
     * Returns the pool of async requests.
     * @return PoolInterface|void
     */
    public function getPool()
    {
        return $this->pool;
    }

    /**
     * Sets the pool of async requests.
     * @param PoolInterface|void $pool
     * @return $this
     */
    public function setPool(PoolInterface $pool)
    {
        $this->pool = $pool;

        return $this;
    }
}
