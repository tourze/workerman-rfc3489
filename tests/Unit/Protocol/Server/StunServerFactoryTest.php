<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerFactory;

/**
 * StunServerFactory 测试类
 */
class StunServerFactoryTest extends TestCase
{
    public function testCreateStunServer()
    {
        $factory = new StunServerFactory();
        
        $this->assertInstanceOf(StunServerFactory::class, $factory);
    }
}