<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\TransactionState;

/**
 * TransactionState 测试类
 */
class TransactionStateTest extends TestCase
{
    public function testEnumCases()
    {
        $this->assertSame('PENDING', TransactionState::PENDING->name);
        $this->assertSame('COMPLETED', TransactionState::COMPLETED->name);
        $this->assertSame('TIMEOUT', TransactionState::TIMEOUT->name);
        $this->assertSame('FAILED', TransactionState::FAILED->name);
        $this->assertSame('CANCELLED', TransactionState::CANCELLED->name);
    }
    
    public function testGetLabel()
    {
        $this->assertSame('待处理', TransactionState::PENDING->getLabel());
        $this->assertSame('已完成', TransactionState::COMPLETED->getLabel());
        $this->assertSame('超时', TransactionState::TIMEOUT->getLabel());
        $this->assertSame('失败', TransactionState::FAILED->getLabel());
        $this->assertSame('取消', TransactionState::CANCELLED->getLabel());
    }
}