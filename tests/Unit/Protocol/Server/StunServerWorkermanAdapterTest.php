<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerWorkermanAdapter;

/**
 * StunServerWorkermanAdapter 测试类
 */
class StunServerWorkermanAdapterTest extends TestCase
{
    public function testConstructor()
    {
        $messageRouter = $this->createMock(\Tourze\Workerman\RFC3489\Protocol\Server\StunMessageRouter::class);
        $adapter = new StunServerWorkermanAdapter('127.0.0.1', 3478, $messageRouter);
        
        $this->assertInstanceOf(StunServerWorkermanAdapter::class, $adapter);
    }
}