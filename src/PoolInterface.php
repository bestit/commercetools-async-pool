<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Request\ClientRequestInterface;
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
     * Adds a promise to this pool.
     * @param ClientRequestInterface $request
     * @param callable $startingSuccessCallback Overwrite the starting success callback.
     * @param callable $startingFailCallback Overwrite the failing success callback.
     * @return ApiResponseInterface
     */
    public function addPromise(
        ClientRequestInterface $request,
        callable $startingSuccessCallback = null,
        callable $startingFailCallback = null
    ): ApiResponseInterface;

    /**
     * Flushes the collected pull of promises.
     * @return void
     */
    public function flush();
}
