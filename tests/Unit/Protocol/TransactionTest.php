<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Transaction;

/**
 * Transaction 测试类
 */
class TransactionTest extends TestCase
{
    public function testConstructor()
    {
        $request = $this->createMock(\Tourze\Workerman\RFC3489\Message\StunMessage::class);
        $transaction = new Transaction($request, '127.0.0.1', 3478);
        
        $this->assertInstanceOf(Transaction::class, $transaction);
    }
    
    public function testGetTransactionId()
    {
        $request = $this->createMock(\Tourze\Workerman\RFC3489\Message\StunMessage::class);
        $request->method('getTransactionId')->willReturn('test123456789abcdef');
        
        $transaction = new Transaction($request, '127.0.0.1', 3478);
        
        $transactionId = $transaction->getTransactionId();
        $this->assertSame('test123456789abcdef', $transactionId);
    }
}