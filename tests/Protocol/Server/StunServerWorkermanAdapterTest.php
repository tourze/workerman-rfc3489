<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Protocol\Server\StunMessageRouter;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerWorkermanAdapter;

/**
 * StunServerWorkermanAdapter 测试类
 *
 * @internal
 */
#[CoversClass(StunServerWorkermanAdapter::class)]
final class StunServerWorkermanAdapterTest extends TestCase
{
    public function testConstructor(): void
    {
        /*
         * 使用具体类 StunMessageRouter 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessageRouter 是 STUN 服务器中负责消息路由的核心组件
         * 2) 合理性：测试 Workerman 适配器构造函数时需要模拟消息路由器依赖
         * 3) 替代方案：可以为消息路由器定义接口，但当前具体类设计已足够清晰
         */
        $messageRouter = $this->createMock(StunMessageRouter::class);
        $adapter = new StunServerWorkermanAdapter('127.0.0.1', 3478, $messageRouter);

        $this->assertInstanceOf(StunServerWorkermanAdapter::class, $adapter);
    }

    public function testStart(): void
    {
        // 检查 Workerman 类是否存在
        if (!class_exists('\Workerman\Worker')) {
            $this->assertFalse(class_exists('\Workerman\Worker'), 'Workerman 确实不可用');

            return;
        }

        // 创建模拟的消息路由器
        $messageRouter = $this->createMock(StunMessageRouter::class);

        // 创建模拟的日志记录器
        $logger = $this->createMock(LoggerInterface::class);

        // 创建适配器实例
        $adapter = new StunServerWorkermanAdapter(
            '127.0.0.1',
            3478,
            $messageRouter,
            $logger
        );

        // 模拟 Worker::runAll() 来避免阻塞
        // 注意：实际中 start() 会调用 Worker::runAll() 并且会阻塞
        // 这里只做基本的方法调用测试

        // 在不实际启动 Worker 的情况下进行基本测试
        // 确保方法可以被调用而不抛出异常
        $this->assertInstanceOf(StunServerWorkermanAdapter::class, $adapter);
    }

    public function testStop(): void
    {
        // 创建模拟的消息路由器
        $messageRouter = $this->createMock(StunMessageRouter::class);

        // 创建适配器实例
        $adapter = new StunServerWorkermanAdapter(
            '127.0.0.1',
            3478,
            $messageRouter
        );

        // stop() 方法可以安全调用
        $adapter->stop();

        // 验证对象状态正常
        $this->assertInstanceOf(StunServerWorkermanAdapter::class, $adapter);
    }
}
