<?php

namespace BestIt\CTAsyncPool\Tests;

use BestIt\CTAsyncPool\PoolAwareTrait;
use BestIt\CTAsyncPool\PoolInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class PoolAwareTraitTest
 * @author blange <lange@bestit-online.de>
 * @category Tests
 * @package BestIt\CTAsyncPool
 * @version $id$
 */
class PoolAwareTraitTest extends TestCase
{
    /**
     * The tested class.
     * @var PoolAwareTrait
     */
    private $fixture = null;

    /**
     * Sets up the test.
     * @return void
     */
    public function setUp()
    {
        $this->fixture = static::getMockForTrait(PoolAwareTrait::class);
    }

    /**
     * Checks the getter and setter.
     * @return void
     */
    public function testGetAndSetPool()
    {
        static::assertNull($this->fixture->getPool(), 'Default return was wrong.');

        static::assertSame(
            $this->fixture,
            $this->fixture->setPool($mock = static::createMock(PoolInterface::class)),
            'Setter was not fluent.'
        );

        static::assertSame($mock, $this->fixture->getPool(), 'Return value was wrong.');
    }
}
