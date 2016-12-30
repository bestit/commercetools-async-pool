<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Client;
use Commercetools\Core\Request\ClientRequestInterface;

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
        $this->setPromises([]);
    }

    /**
     * Pool constructor.
     * @param Client $client
     * @param int $ticks
     */
    public function __construct(Client $client, $ticks = self::DEFAULT_TICKS)
    {
        $this
            ->setClient($client)
            ->setPromises([])
            ->setTicks($ticks);
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
     * @return void
     */
    public function addPromise(ClientRequestInterface $request, callable $onResolve = null, callable $onReject = null)
    {
        // The identifier can not be used otherwise.
        $request->setIdentifier($identifier = spl_object_hash($request));

        $this->getClient()->addBatchRequest($request);

        $this->promises[$identifier] = [self::PROMISE_KEY_RESOLVE => $onResolve, self::PROMISE_KEY_REJECT => $onReject];

        if (count($this) >= $this->getTicks()) {
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
        return count($this->getPromises());
    }

    /**
     * Flushes the collected pull of promises.
     * @return void
     */
    public function flush()
    {
        // Prevent an endless loop and work on a batch copy.
        $promises = $this->getPromises();
        $this->setPromises([]);

        foreach ($this->getClient()->executeBatch() as $identifier => $response) {
            $promise = $promises[$identifier];

            if ($response->isError()) {
                if ($promise[self::PROMISE_KEY_REJECT]) {
                    call_user_func($promise[self::PROMISE_KEY_REJECT], $response);
                }
            } else {
                if ($promise[self::PROMISE_KEY_RESOLVE]) {
                    call_user_func($promise[self::PROMISE_KEY_RESOLVE], $response->toObject());
                }
            }
        }
    }

    /**
     * Returns the commercetools client.
     * @return Client
     */
    private function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Returns the promises.
     * @return array
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
     * Sets the client.
     * @param Client $client
     * @return Pool
     */
    private function setClient(Client $client): Pool
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Sets the promises.
     * @param array $promises
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
