<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Request\ClientRequestInterface;
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
     *
     * The commercetools api breaks chaining promises, because its AbstractApiResponse::wait/then throws an exception if
     * guzzle tries to chain it with promise_for. And guzzle on the other hand "exits", if you try to force the
     * FullfilledPromise, because the fullfilled response has a then method. So we need the workaround with the
     * callbacks or a custom "FakePromise".
     * @param ClientRequestInterface $request
     * @param callable|void $onResolve Callback on the successful response.
     * @param callable|void $onReject Callback for an error.
     * @return void
     */
    public function addPromise(ClientRequestInterface $request, callable $onResolve = null, callable $onReject = null);

    /**
     * Flushes the collected pull of promises.
     * @return void
     */
    public function flush();
}
