<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Response\ApiResponseInterface;

/**
 * Collects the promises.
 * @author blange <code@b3nl.de>
 * @package BestIt\CTAsyncPool
 * @version $id$
 */
class Pool implements PoolInterface
{
    /**
     * The promise pool.
     * @var ApiResponseInterface[]
     */
    private $promises = [];

    /**
     * How many promises can be collected before the pool gets flushed.
     * @var int
     */
    private $ticks = 0;

    /**
     * Pool constructor.
     * @param int $ticks
     */
    public function __construct($ticks = self::DEFAULT_TICKS)
    {
        $this->setTicks($ticks);
    }

    /**
     * Empties the pool on clone.
     */
    public function __clone()
    {
        $this->setPromises([]);
    }

    /**
     * Adds a promise to this pool.
     * @param ApiResponseInterface $promise
     * @return ApiResponseInterface Fluent interface for the promise, so that you can call then etc. now.
     */
    public function addPromise(ApiResponseInterface $promise): ApiResponseInterface
    {
        $this->promises[spl_object_hash($promise)] = $promise;

        if (count($this) >= $this->getTicks()) {
            $this->flush();
        }

        return $promise;
    }

    /**
     * How many promises are there?
     * @return int
     */
    public function count(): int
    {
        return count($this->getPromises());
    }

    /**
     * Flushes the collected pull of promises.
     * @return PoolInterface
     */
    public function flush(): PoolInterface
    {
        while (count($this)) {
            // Prevent an endless loop and work on a batch copy.
            $copy = $this->getPromises();
            $this->setPromises([]);

            \GuzzleHttp\Promise\unwrap($copy);
        }

        return $this;
    }

    /**
     * Returns the promises.
     * @return ApiResponseInterface[]
     */
    private function getPromises(): array
    {
        return $this->promises;
    }

    /**
     * How many promises can be collected before the pool gets flushed.
     * @return int
     */
    private function getTicks(): int
    {
        return $this->ticks;
    }

    /**
     * Sets the promises.
     * @param ApiResponseInterface[] $promises
     * @return Pool
     */
    private function setPromises(array $promises): Pool
    {
        $this->promises = $promises;

        return $this;
    }

    /**
     * How many promises can be collected before the pool gets flushed.
     * @param int $ticks
     * @return Pool
     */
    private function setTicks(int $ticks): Pool
    {
        $this->ticks = $ticks;

        return $this;
    }
}
