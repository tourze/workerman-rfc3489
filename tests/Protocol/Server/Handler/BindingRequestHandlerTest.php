<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\BindingRequestHandler;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * BindingRequestHandler 测试类
 *
 * @internal
 */
#[CoversClass(BindingRequestHandler::class)]
final class BindingRequestHandlerTest extends TestCase
{
    public function testClassExists(): void
    {
        $handler = new BindingRequestHandler(
            $this->createMock(StunTransport::class),
            '192.168.1.1',
            3479,
            null,
            $this->createMock(LoggerInterface::class)
        );
        $this->assertInstanceOf(BindingRequestHandler::class, $handler);
    }

    public function testHandleMessage(): void
    {
        // 创建模拟的依赖对象
        $transport = $this->createMock(StunTransport::class);
        $logger = $this->createMock(LoggerInterface::class);

        // 创建处理器实例
        $handler = new BindingRequestHandler(
            $transport,
            '192.168.1.1',
            3479,
            null,
            $logger
        );

        // 创建模拟的请求消息
        $request = $this->createMock(StunMessage::class);
        $request->method('getAttributes')->willReturn([]);
        $request->method('getMethod')->willReturn(MessageMethod::BINDING);
        $request->method('getTransactionId')->willReturn('1234567890abcdef');

        // 设置模拟的传输层返回本地地址
        $transport->method('getLocalAddress')->willReturn(['127.0.0.1', 3478]);

        // 调用处理方法
        $response = $handler->handleMessage($request, '192.168.1.100', 12345);

        // 验证返回了响应消息
        $this->assertInstanceOf(StunMessage::class, $response);
    }
}
