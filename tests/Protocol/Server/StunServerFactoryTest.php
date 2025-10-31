<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerFactory;

/**
 * StunServerFactory 测试类
 *
 * @internal
 */
#[CoversClass(StunServerFactory::class)]
final class StunServerFactoryTest extends TestCase
{
    public function testCreateStunServer(): void
    {
        $factory = new StunServerFactory();

        $this->assertInstanceOf(StunServerFactory::class, $factory);
    }
}
