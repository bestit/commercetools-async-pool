<?php

namespace BestIt\CTAsyncPool\Tests;

use BestIt\CTAsyncPool\Pool;
use BestIt\CTAsyncPool\PoolInterface;
use Commercetools\Core\Client;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject;

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
}
