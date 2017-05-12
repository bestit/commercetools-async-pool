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
     * Empties the pool on clone.
     */
    public function __clone()
    {
        $this->setPromises([]);
    }

    /**
     * Pool constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this
            ->setClient($client)
            ->setPromises([]);
    }

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
    ): ApiResponseInterface {
        $return = $this->promises[$request->getIdentifier()] = $this->createStartingPromise(
            $request,
            $startingSuccessCallback,
            $startingFailCallback
        );

        return $return;
    }

    /**
     * Makes a ctp response out of the default async response and returns the promise for the next chaining level.
     * @param ClientRequestInterface $request
     * @param callable $startingSuccessCallback Overwrite the starting success callback.
     * @param callable $startingFailCallback Overwrite the failing success callback.
     * @return ApiResponseInterface
     */
    private function createStartingPromise(
        ClientRequestInterface $request,
        callable $startingSuccessCallback = null,
        callable $startingFailCallback = null
    ): ApiResponseInterface {
        if (!$startingSuccessCallback) {
            $startingSuccessCallback = function (ResponseInterface $response) use ($request) {
                return $request->mapFromResponse($request->buildResponse($response));
            };
        }

        if (!$startingFailCallback) {
            $startingFailCallback = function (RequestException $exception) {
                return ApiException::create($exception->getRequest(), $exception->getResponse(), $exception);
            };
        }

        return $this->getClient()->executeAsync($request)->then($startingSuccessCallback, $startingFailCallback);
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
}
