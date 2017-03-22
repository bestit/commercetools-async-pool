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
     * Sets up the test.
     * @return void
     */
    public function setUp()
    {
        $this->fixture = new Pool($this->client = static::createMock(Client::class));
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
     * Checks the declared constant.
     * @return void
     */
    public function testTickConstant()
    {
        static::assertSame(100, Pool::DEFAULT_TICKS);
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
}
