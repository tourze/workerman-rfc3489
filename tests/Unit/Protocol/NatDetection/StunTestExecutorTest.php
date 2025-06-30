<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\NatDetection;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunTestExecutor;

/**
 * StunTestExecutor 测试类
 */
class StunTestExecutorTest extends TestCase
{
    public function testConstructor()
    {
        $requestSender = $this->createMock(\Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender::class);
        $executor = new StunTestExecutor($requestSender);
        
        $this->assertInstanceOf(StunTestExecutor::class, $executor);
    }
}