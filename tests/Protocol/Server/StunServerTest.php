<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Protocol\Server\StunMessageRouter;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServer;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerStandaloneAdapter;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * StunServer 测试类
 *
 * @internal
 */
#[CoversClass(StunServer::class)]
final class StunServerTest extends TestCase
{
    public function testClassExists(): void
    {
        $server = new StunServer(
            '127.0.0.1',
            3478,
            '127.0.0.1',
            3479,
            $this->createMock(StunTransport::class),
            $this->createMock(StunMessageRouter::class),
            $this->createMock(StunServerStandaloneAdapter::class),
            $this->createMock(LoggerInterface::class)
        );
        $this->assertInstanceOf(StunServer::class, $server);
    }

    public function testStart(): void
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);

        // 创建模拟的消息路由器
        $messageRouter = $this->createMock(StunMessageRouter::class);

        // 创建模拟的独立适配器
        $standaloneAdapter = $this->createMock(StunServerStandaloneAdapter::class);

        // 创建服务器实例
        $server = new StunServer(
            '127.0.0.1',
            3478,
            '192.168.1.1',
            3479,
            $transport,
            $messageRouter,
            $standaloneAdapter
        );

        // 验证 standaloneAdapter 的 start() 方法被调用
        $standaloneAdapter->expects($this->once())->method('start');

        // 调用 start() 方法
        $server->start();

        // 验证服务器正在运行
        $this->assertTrue($server->isRunning());
    }

    public function testStop(): void
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);

        // 创建模拟的消息路由器
        $messageRouter = $this->createMock(StunMessageRouter::class);

        // 创建模拟的独立适配器
        $standaloneAdapter = $this->createMock(StunServerStandaloneAdapter::class);

        // 创建服务器实例
        $server = new StunServer(
            '127.0.0.1',
            3478,
            '192.168.1.1',
            3479,
            $transport,
            $messageRouter,
            $standaloneAdapter
        );

        // 验证 standaloneAdapter 的 stop() 方法被调用
        $standaloneAdapter->expects($this->once())->method('stop');

        // 先启动再停止
        $server->start();
        $server->stop();

        // 验证服务器停止运行
        $this->assertFalse($server->isRunning());
    }
}
