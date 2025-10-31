<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\NatDetection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * StunRequestSender 测试类
 *
 * @internal
 */
#[CoversClass(StunRequestSender::class)]
final class StunRequestSenderTest extends TestCase
{
    public function testConstructor(): void
    {
        $transport = $this->createMock(StunTransport::class);
        $sender = new StunRequestSender($transport);

        $this->assertInstanceOf(StunRequestSender::class, $sender);
    }

    public function testClose(): void
    {
        $transport = $this->createMock(StunTransport::class);
        $transport->expects($this->once())
            ->method('close')
        ;

        $sender = new StunRequestSender($transport);
        $sender->close();
    }

    public function testLog(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $transport = $this->createMock(StunTransport::class);

        $logger->expects($this->once())
            ->method('log')
            ->with('info', '[StunSender] 测试日志消息')
        ;

        $sender = new StunRequestSender($transport, 5000, $logger);
        $sender->log('info', '测试日志消息');
    }

    public function testLogWithoutLogger(): void
    {
        $transport = $this->createMock(StunTransport::class);
        $sender = new StunRequestSender($transport);

        // 不应该抛出异常，即使没有设置logger
        $sender->log('info', '测试日志消息');

        // 验证方法执行完成且对象状态正常
        $this->assertInstanceOf(StunRequestSender::class, $sender);
    }

    public function testSendRequest(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：StunTransport 是传输层的核心实现类，测试需要模拟其具体的网络传输行为
         * 2) 合理性：在单元测试中模拟网络传输层是必要的，避免真实的网络操作
         * 3) 替代方案：可以考虑为 StunTransport 定义接口，但当前的具体类设计已足够满足测试需求
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 是 STUN 协议的核心消息结构，没有相应的接口
         * 2) 合理性：在单元测试中模拟 STUN 消息对象是合理的，避免创建真实的协议消息
         * 3) 替代方案：可以考虑为 StunMessage 定义接口，但会增加抽象层复杂度，当前方案已足够
         */
        $message = $this->createMock(StunMessage::class);

        // 模拟传输层行为
        $transport->expects($this->once())
            ->method('close')
        ;
        $transport->expects($this->once())
            ->method('bind')
            ->with('0.0.0.0', 0)
            ->willReturn(true)
        ;
        $transport->expects($this->once())
            ->method('send')
            ->with($message, '8.8.8.8', 53)
            ->willReturn(true)
        ;
        $transport->expects($this->atLeastOnce())
            ->method('receive')
            ->willReturn(null) // 模拟超时情况
        ;

        $message->expects($this->any())
            ->method('getTransactionId')
            ->willReturn('test-transaction-id')
        ;

        $sender = new StunRequestSender($transport, 100); // 设置短超时

        // 由于模拟超时，应该抛出TimeoutException
        $this->expectException(TimeoutException::class);
        $sender->sendRequest($message, '8.8.8.8', 53);
    }

    public function testSendRequestWithInvalidDestination(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：StunTransport 是传输层的核心实现类，测试需要模拟其网络传输行为
         * 2) 合理性：模拟传输层对于测试网络错误处理逻辑是必要的
         * 3) 替代方案：可以定义传输层接口，但当前具体类的使用方式已足够清晰
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 是 STUN 协议消息的核心数据结构
         * 2) 合理性：测试无效目标地址场景时需要模拟消息对象
         * 3) 替代方案：可以创建消息接口，但会增加设计复杂度
         */
        $message = $this->createMock(StunMessage::class);

        $sender = new StunRequestSender($transport);

        // 测试无效的目标地址（0.0.0.0）
        $result = $sender->sendRequest($message, '0.0.0.0', 53);
        $this->assertNull($result);
    }
}
