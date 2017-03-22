<?php

namespace BestIt\CTAsyncPool\Tests;

use BestIt\CTAsyncPool\Pool;
use BestIt\CTAsyncPool\PoolInterface;
use Commercetools\Core\Client;
use Commercetools\Core\Model\Customer\CustomerCollection;
use Commercetools\Core\Request\Customers\CustomerQueryRequest;
use Commercetools\Core\Response\ApiResponseInterface;
use Commercetools\Core\Response\PagedQueryResponse;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;

/**
 * Class PoolTest.
 * @author blange <lange@bestit-online.de>
 * @category Tests
 * @package BestIt\CTAsyncPool
 * @todo Add more testing.
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
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockedRequest(): PHPUnit_Framework_MockObject_MockObject
    {
        $this->client
            ->method('executeAsync')
            ->with($request = static::createMock(CustomerQueryRequest::class))
            ->will($this->returnValue($response1 = static::createMock(ApiResponseInterface::class)));

        $response2 = static::createMock(ResponseInterface::class);

        $request
            ->method('buildResponse')
            ->with($response2)
            ->willReturn($response3 = static::createMock(PagedQueryResponse::class));

        $request
            ->method('mapResponse')
            ->with($response3)
            ->willReturn($response4 = static::createMock(CustomerCollection::class));

        $response1
            ->method('then')
            ->willReturn($response3);
        return $request;
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
     * Checks the fluent interface of the pool object.
     * @return void
     */
    public function testAddPromiseFluent()
    {
        static::assertSame($this->fixture, $this->fixture->addPromise($this->getMockedRequest()));
    }

    /**
     * Checks if the pool flushes after the tick rate is overflowed.
     * @return void
     */
    public function testAddPromiseNoWaitOnFlush()
    {
        $this->fixture = new Pool($this->client = static::createMock(Client::class), 1);

        static::assertCount(0, $this->fixture, 'The empty pool should return 0.');

        $this->fixture->addPromise($this->getMockedRequest());

        static::assertCount(0, $this->fixture, 'There should be no promises after flush.');
    }

    /**
     * Checks if the pool waits on the flush to clear its promises.
     * @return void
     */
    public function testAddPromiseWaitOnFlush()
    {
        static::assertCount(0, $this->fixture, 'The empty pool should return 0.');

        $this->fixture->addPromise($this->getMockedRequest());

        static::assertCount(1, $this->fixture, 'We added on promise to the pool.');

        $this->fixture->flush();

        static::assertCount(0, $this->fixture, 'There should be no promises after flush.');
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

    /**
     * Checks the declared constant.
     * @return void
     */
    public function testTickConstant()
    {
        static::assertSame(100, Pool::DEFAULT_TICKS);
    }
}
