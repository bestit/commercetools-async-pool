<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Response\ApiResponseInterface;
use Countable;

/**
 * Collects the promises.
 * @author blange <code@b3nl.de>
 * @package BestIt\CTAsyncPool
 * @version $id$
 */
interface PoolInterface extends Countable
{
    /**
     * The default tickets.
     * @var int
     */
    const DEFAULT_TICKS = 100;

    /**
     * Adds a promise to this pool.
     * @param ApiResponseInterface $promise
     * @return ApiResponseInterface Fluent interface for the promise, so that you can call then etc. now.
     */
    public function addPromise(ApiResponseInterface $promise) : ApiResponseInterface;

    /**
     * Flushes the collected pull of promises.
     * @return PoolInterface
     */
    public function flush() : PoolInterface;
}
