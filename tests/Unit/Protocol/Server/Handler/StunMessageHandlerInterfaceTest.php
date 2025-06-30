<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;

/**
 * StunMessageHandlerInterface 测试类
 */
class StunMessageHandlerInterfaceTest extends TestCase
{
    public function testInterfaceExists()
    {
        $this->assertTrue(interface_exists(StunMessageHandlerInterface::class));
    }
    
    public function testInterfaceMethods()
    {
        $reflection = new \ReflectionClass(StunMessageHandlerInterface::class);
        
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);
        
        $this->assertContains('handleMessage', $methodNames);
    }
}