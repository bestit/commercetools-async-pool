<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Client;
use Commercetools\Core\Error\ApiException;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Response\AbstractApiResponse;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise as P;
use Psr\Http\Message\ResponseInterface;

/**
 * Collects the promises.
 * @author blange <code@b3nl.de>
 * @package BestIt\CTAsyncPool
 * @version $id$
 */
class Pool implements PoolInterface
{
    /**
     * This key is used to fetch the reject callback from the promise array.
     * @var string
     */
    const PROMISE_KEY_REJECT = 'onReject';

    /**
     * This key is used to fetch the resolve callback from the promise array.
     * @var string
     */
    const PROMISE_KEY_RESOLVE = 'onResolve';

    /**
     * The commercetools client.
     * @var Client
     */
    private $client = null;

    /**
     * The promise pool.
     * @var array ['onResolve' => Success-Callback, 'onReject' => Reject-Callback]
     */
    private $promises = [];

    /**
     * How many promises can be collected before the pool gets flushed.
     * @var int
     */
    private $ticks = 0;

    /**
     * Empties the pool on clone.
     */
    public function __clone()
    {
        $this->promises = [];
    }

    /**
     * Pool constructor.
     * @param Client $client
     * @param int $ticks
     */
    public function __construct(Client $client, int $ticks = self::DEFAULT_TICKS)
    {
        $this->ticks = $ticks;
        $this->client = $client;
    }

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
     * @return $this
     */
    public function addPromise(ClientRequestInterface $request, callable $onResolve = null, callable $onReject = null)
    {
        $promise = $this->client->executeAsync($request)
            ->then(
                function (ResponseInterface $response) use ($request) {
                    $apiResponse = $request->buildResponse($response);

                    return $request->mapFromResponse($apiResponse);
                },
                function (RequestException $exception ) {
                    $response = $exception->getResponse();
                    return ApiException::create($exception->getRequest(), $response, $exception);
                }
            )
            ->then($onResolve, $onReject);

        $this->promises[$request->getIdentifier()] = $promise;

        if (count($this) >= $this->ticks) {
            $this->flush();
        }

        return $this;
    }

    /**
     * How many promises are there?
     * @return int
     */
    public function count(): int
    {
        return count($this->promises);
    }

    /**
     * Flushes the collected pull of promises.
     * @return void
     */
    public function flush()
    {
        P\settle($this->promises)->wait();

        $this->promises = [];
    }
}
