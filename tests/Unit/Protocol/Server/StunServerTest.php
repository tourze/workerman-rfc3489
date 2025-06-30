<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServer;

/**
 * StunServer 测试类
 */
class StunServerTest extends TestCase
{
    public function testClassExists()
    {
        $this->assertTrue(class_exists(\Tourze\Workerman\RFC3489\Protocol\Server\StunServer::class));
    }
}