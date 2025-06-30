<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\NatDetection;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender;

/**
 * StunRequestSender 测试类
 */
class StunRequestSenderTest extends TestCase
{
    public function testConstructor()
    {
        $transport = $this->createMock(\Tourze\Workerman\RFC3489\Transport\StunTransport::class);
        $sender = new StunRequestSender($transport);
        
        $this->assertInstanceOf(StunRequestSender::class, $sender);
    }
}