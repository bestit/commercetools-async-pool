<?php

namespace BestIt\CTAsyncPool;

use Commercetools\Core\Client;
use Commercetools\Core\Error\ApiException;
use Commercetools\Core\Request\ClientRequestInterface;
use Commercetools\Core\Response\ApiResponseInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Promise as Promise;
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
     * The commercetools client.
     * @var Client
     */
    private $client = null;

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
    public function __construct(Client $client, $ticks = -1)
    {
        $this
            ->setClient($client)
            ->setPromises([])
            ->setTicks($ticks);
    }

    /**
     * Adds a promise to this pool.
     * @param ClientRequestInterface $request
     * @return PoolInterface|ApiResponseInterface
     */
    public function addPromise(ClientRequestInterface $request, bool $forChaining = true) {
        $return = $this->promises[$request->getIdentifier()] = $this->createStartingPromise($request);

        if (!$forChaining) {
            $this->flushOnOverflow();
            $return = $this;
        }

        return $return;
    }

    /**
     * Makes a ctp response out of the default async response and returns the promise for the next chaining level.
     * @param ClientRequestInterface $request
     * @return ApiResponseInterface
     */
    private function createStartingPromise(ClientRequestInterface $request): ApiResponseInterface
    {
        return $this->getClient()->executeAsync($request)->then(
            function (ResponseInterface $response) use ($request) {
                return $request->mapFromResponse($request->buildResponse($response));
            },
            function (RequestException $exception) {
                return ApiException::create($exception->getRequest(), $exception->getResponse(), $exception);
            }
        );
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
        Promise\settle($promises)->wait();
    }

    /**
     * Flushes the pool of promises if we overflow the tick limit.
     * @return void
     */
    private function flushOnOverflow()
    {
        $ticks = $this->getTicks();

        if ($ticks > -1 && count($this) >= $ticks) {
            $this->flush();
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
