<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server\Handler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\SharedSecretRequestHandler;

/**
 * SharedSecretRequestHandler 测试类
 *
 * @internal
 */
#[CoversClass(SharedSecretRequestHandler::class)]
final class SharedSecretRequestHandlerTest extends TestCase
{
    public function testClassExists(): void
    {
        $handler = new SharedSecretRequestHandler(
            $this->createMock(LoggerInterface::class)
        );
        $this->assertInstanceOf(SharedSecretRequestHandler::class, $handler);
    }

    public function testHandleMessage(): void
    {
        // 创建模拟的日志记录器
        $logger = $this->createMock(LoggerInterface::class);

        // 创建处理器实例
        $handler = new SharedSecretRequestHandler($logger);

        // 创建模拟的请求消息
        $request = $this->createMock(StunMessage::class);
        $request->method('getMethod')->willReturn(MessageMethod::SHARED_SECRET);
        $request->method('getTransactionId')->willReturn('1234567890abcdef');

        // 调用处理方法
        $response = $handler->handleMessage($request, '192.168.1.100', 12345);

        // 验证返回了响应消息
        $this->assertInstanceOf(StunMessage::class, $response);
    }
}
