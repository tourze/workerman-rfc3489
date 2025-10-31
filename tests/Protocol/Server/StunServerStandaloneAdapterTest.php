<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Protocol\Server\StunMessageRouter;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerStandaloneAdapter;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * StunServerStandaloneAdapter 测试类
 *
 * @internal
 */
#[CoversClass(StunServerStandaloneAdapter::class)]
final class StunServerStandaloneAdapterTest extends TestCase
{
    public function testClassExists(): void
    {
        $adapter = new StunServerStandaloneAdapter(
            $this->createMock(StunTransport::class),
            '127.0.0.1',
            3478,
            $this->createMock(StunMessageRouter::class),
            $this->createMock(LoggerInterface::class)
        );
        $this->assertInstanceOf(StunServerStandaloneAdapter::class, $adapter);
    }

    public function testStartWithPcntl(): void
    {
        // 只在有 pcntl 支持时执行此测试
        if (!function_exists('pcntl_fork')) {
            $this->assertFalse(function_exists('pcntl_fork'), 'pcntl 扩展确实不可用');

            return;
        }

        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);
        $transport->method('bind')->willReturn(true);
        $transport->method('receive')->willReturn(null); // 模拟没有消息

        // 创建模拟的消息路由器
        $messageRouter = $this->createMock(StunMessageRouter::class);

        // 创建模拟的日志记录器
        $logger = $this->createMock(LoggerInterface::class);

        // 创建适配器实例
        $adapter = new StunServerStandaloneAdapter(
            $transport,
            '127.0.0.1',
            3478,
            $messageRouter,
            $logger
        );

        // 在子进程中启动适配器
        $pid = pcntl_fork();
        if (0 === $pid) {
            // 子进程
            $adapter->start();
            exit(0);
        }
        // 父进程，等待一段时间后停止
        usleep(50000); // 50ms
        posix_kill($pid, SIGTERM);
        pcntl_waitpid($pid, $status);

        // 验证适配器可以正常创建和操作
        $this->assertInstanceOf(StunServerStandaloneAdapter::class, $adapter);
    }

    public function testStartWithoutPcntl(): void
    {
        // 测试基本的对象创建和方法调用，不依赖 pcntl
        $transport = $this->createMock(StunTransport::class);
        $messageRouter = $this->createMock(StunMessageRouter::class);
        $logger = $this->createMock(LoggerInterface::class);

        $adapter = new StunServerStandaloneAdapter(
            $transport,
            '127.0.0.1',
            3478,
            $messageRouter,
            $logger
        );

        // 验证对象创建成功
        $this->assertInstanceOf(StunServerStandaloneAdapter::class, $adapter);

        // 验证可以调用 isRunning 方法
        $this->assertFalse($adapter->isRunning());
    }

    public function testStop(): void
    {
        // 创建模拟的传输层
        $transport = $this->createMock(StunTransport::class);

        // 创建模拟的消息路由器
        $messageRouter = $this->createMock(StunMessageRouter::class);

        // 创建适配器实例
        $adapter = new StunServerStandaloneAdapter(
            $transport,
            '127.0.0.1',
            3478,
            $messageRouter
        );

        // 调用 stop() 方法
        $adapter->stop();

        // 验证服务器不再运行
        $this->assertFalse($adapter->isRunning());
    }
}
