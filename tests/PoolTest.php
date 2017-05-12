<?php

namespace BestIt\CTAsyncPool\Tests;

use BestIt\CTAsyncPool\Pool;
use BestIt\CTAsyncPool\PoolInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Customer\CustomerCollection;
use Commercetools\Core\Request\Customers\CustomerQueryRequest;
use Commercetools\Core\Response\ApiResponseInterface;
use Commercetools\Core\Response\PagedQueryResponse;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PoolTest.
 * @author blange <lange@bestit-online.de>
 * @category Tests
 * @package BestIt\CTAsyncPool
 * @todo Test resolving of the promises with a guzzle mock handler.
 * @version $id$
 */
class PoolTest extends TestCase
{
    /**
     * The client used in the fixture.
     * @var Client|PHPUnit_Framework_MockObject_MockObject
     */
    private $client = null;

    /**
     * The tested class.
     * @var Pool
     */
    private $fixture = null;

    /**
     * Returns an "async-mocked" request.
     * @param bool $resolvesPromise Should the promise be resolved?
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockedRequest(bool $resolvesPromise = false): PHPUnit_Framework_MockObject_MockObject
    {
        $request = static::createMock(CustomerQueryRequest::class);

        $this->client
            ->method('executeAsync')
            ->with($request)
            ->will($this->returnValue($response1 = static::createMock(ApiResponseInterface::class)));

        $response2 = static::createMock(ResponseInterface::class);

        $request
            ->expects(!$resolvesPromise ? static::any() : static::once())
            ->method('buildResponse')
            ->with($response2)
            ->willReturn($response3 = static::createMock(PagedQueryResponse::class));

        $request
            ->expects(!$resolvesPromise ? static::any() : static::once())
            ->method('mapFromResponse')
            ->with($response3)
            ->willReturn($response4 = static::createMock(CustomerCollection::class));

        $response1
            ->expects(!$resolvesPromise ? static::any() : static::once())
            ->method('then')
            ->with($this->callback(function (callable $callback) use ($response2) {
                static::assertInstanceOf(CustomerCollection::class, $callback($response2));

                return true;
            }))
            ->willReturn($response3);

        return $request;
    }

    /**
     * Ceates a test client with the given responses.
     * @param array $responses
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTestClient(array $responses)
    {
        $authMock = $this->createPartialMock(Client\OAuth\Manager::class, ['getToken']);
        $authMock
            ->method('getToken')
            ->will($this->returnValue(new Client\OAuth\Token(uniqid())));

        /** @var Client|PHPUnit_Framework_MockObject_MockObject $client */
        $client = $this->createPartialMock(Client::class, ['getOauthManager']);
        $client
            ->method('getOauthManager')
            ->will($this->returnValue($authMock));

        $mock = new MockHandler($responses);

        $handler = HandlerStack::create($mock);
        $client->getHttpClient(['handler' => $handler]);
        $client->getOauthManager()->getHttpClient(['handler' => $handler]);

        return $client;
    }

    /**
     * Sets up the test.
     * @return void
     */
    public function setUp()
    {
        $this->fixture = new Pool($this->client = static::createMock(Client::class));
    }

    /**
     * Checks that the promise is returned, if we want to use chaining.
     * @return void
     */
    public function testAddPromiseFluent()
    {
        static::assertInstanceOf(PagedQueryResponse::class, $this->fixture->addPromise($this->getMockedRequest()));
    }

    /**
     * Checks if the clone empties the promise queue.
     * @return void
     */
    public function testCloneAndEmpty()
    {
        $this->fixture->addPromise($this->getMockedRequest());

        static::assertCount(1, $this->fixture, 'There should be one promise.');

        $clonedFixture = clone $this->fixture;

        static::assertCount(0, $clonedFixture, 'After cloning there should be no promise.');
    }

    /**
     * Checks the default value for the count.
     * @covers Pool::count()
     * @return void
     */
    public function testCountDefault()
    {
        static::assertSame(0, count($this->fixture));
    }

    /**
     * Checks if the correct interface is used.
     * @return void
     */
    public function testInterface()
    {
        static::assertInstanceOf(PoolInterface::class, $this->fixture);
    }
}
