<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;

/**
 * StunMessageHandlerInterface 测试类
 *
 * @internal
 */
#[CoversClass(StunMessageHandlerInterface::class)]
final class StunMessageHandlerInterfaceTest extends TestCase
{
    public function testInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(StunMessageHandlerInterface::class));
    }

    public function testInterfaceMethods(): void
    {
        $reflection = new \ReflectionClass(StunMessageHandlerInterface::class);

        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method) => $method->getName(), $methods);

        $this->assertContains('handleMessage', $methodNames);
    }
}
