<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\Transaction;

/**
 * Transaction 测试类
 *
 * @internal
 */
#[CoversClass(Transaction::class)]
final class TransactionTest extends TestCase
{
    public function testConstructor(): void
    {
        /*
         * 使用具体类 StunMessage 创建 Mock 的原因：
         * 1. Transaction 类的构造函数直接依赖 StunMessage 具体类，而不是接口
         * 2. StunMessage 是 STUN 协议的核心消息类，包含特定的方法如 getTransactionId()
         * 3. 在 RFC3489 协议实现中，Transaction 必须与具体的 STUN 消息格式耦合
         * 4. 目前没有抽象接口，因为 STUN 消息格式是标准化的
         */
        $request = $this->createMock(StunMessage::class);
        $transaction = new Transaction($request, '127.0.0.1', 3478);

        $this->assertInstanceOf(Transaction::class, $transaction);
    }

    public function testGetTransactionId(): void
    {
        /*
         * 使用具体类 StunMessage 创建 Mock 的原因：
         * 1. getTransactionId() 方法是 StunMessage 类特有的方法
         * 2. STUN 协议要求事务ID必须从消息中提取，这是协议规范
         * 3. Transaction 类设计为与 STUN 消息紧密耦合，符合协议实现需求
         */
        $request = $this->createMock(StunMessage::class);
        $request->method('getTransactionId')->willReturn('test123456789abcdef');

        $transaction = new Transaction($request, '127.0.0.1', 3478);

        $transactionId = $transaction->getTransactionId();
        $this->assertSame('test123456789abcdef', $transactionId);
    }

    public function testCanRetry(): void
    {
        /*
         * 使用具体类 StunMessage 创建 Mock 的原因：
         * 1. 测试重试逻辑需要真实的 STUN 消息作为载体
         * 2. RFC3489 协议规定了重试机制必须基于具体的 STUN 消息
         * 3. Transaction 类的重试逻辑与 STUN 消息的事务ID直接相关
         */
        $request = $this->createMock(StunMessage::class);
        $request->method('getTransactionId')->willReturn('test123456789abcdef');

        $transaction = new Transaction($request, '127.0.0.1', 3478, 5000, 3);

        $this->assertTrue($transaction->canRetry());

        $transaction->incrementRetry();
        $this->assertTrue($transaction->canRetry());

        $transaction->incrementRetry();
        $transaction->incrementRetry();
        $this->assertFalse($transaction->canRetry());
    }

    public function testIncrementRetry(): void
    {
        /*
         * 使用具体类 StunMessage 创建 Mock 的原因：
         * 1. incrementRetry() 方法测试需要有效的 Transaction 实例
         * 2. Transaction 构造函数要求 StunMessage 参数，这是协议要求
         * 3. 重试计数功能与 STUN 协议的可靠传输机制相关
         */
        $request = $this->createMock(StunMessage::class);
        $request->method('getTransactionId')->willReturn('test123456789abcdef');

        $transaction = new Transaction($request, '127.0.0.1', 3478);

        $this->assertSame(0, $transaction->getCurrentRetry());

        $result = $transaction->incrementRetry();
        $this->assertSame($transaction, $result);
        $this->assertSame(1, $transaction->getCurrentRetry());

        $transaction->incrementRetry();
        $this->assertSame(2, $transaction->getCurrentRetry());
    }

    public function testReset(): void
    {
        /*
         * 使用具体类 StunMessage 创建 Mock 的原因：
         * 1. reset() 方法需要重置 Transaction 到初始状态
         * 2. Transaction 初始化必须依赖 StunMessage，这是架构设计
         * 3. 重置功能是 STUN 事务管理的核心，需要保持与协议一致
         */
        $request = $this->createMock(StunMessage::class);
        $request->method('getTransactionId')->willReturn('test123456789abcdef');

        $transaction = new Transaction($request, '127.0.0.1', 3478);

        $transaction->incrementRetry();
        $transaction->incrementRetry();

        $this->assertSame(2, $transaction->getCurrentRetry());

        $result = $transaction->reset();
        $this->assertSame($transaction, $result);
        $this->assertSame(0, $transaction->getCurrentRetry());
        $this->assertFalse($transaction->isCompleted());
        $this->assertNull($transaction->getResponse());
    }

    public function testWaitForCompletion(): void
    {
        /*
         * 使用具体类 StunMessage 创建 Mock 的原因：
         * 1. waitForCompletion() 需要真实的请求和响应消息对
         * 2. STUN 协议要求请求和响应的事务ID必须匹配
         * 3. StunMessage 包含协议特定的验证逻辑，接口无法提供
         * 4. 异步等待机制是 STUN 协议实现的核心功能
         */
        $request = $this->createMock(StunMessage::class);
        $request->method('getTransactionId')->willReturn('test123456789abcdef');

        /*
         * 响应消息也必须使用具体类，因为：
         * 1. setResponse() 方法会验证响应消息的事务ID
         * 2. 需要模拟完整的 STUN 请求-响应周期
         * 3. 协议要求响应必须是有效的 STUN 消息格式
         */
        $response = $this->createMock(StunMessage::class);
        $response->method('getTransactionId')->willReturn('test123456789abcdef');

        $transaction = new Transaction($request, '127.0.0.1', 3478, 1000);

        $transaction->setResponse($response);

        $result = $transaction->waitForCompletion();
        $this->assertSame($response, $result);
    }
}
