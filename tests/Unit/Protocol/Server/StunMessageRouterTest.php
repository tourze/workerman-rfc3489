<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\StunMessageRouter;

/**
 * StunMessageRouter 测试类
 */
class StunMessageRouterTest extends TestCase
{
    public function testConstructor()
    {
        $router = new StunMessageRouter();
        
        $this->assertInstanceOf(StunMessageRouter::class, $router);
    }
}