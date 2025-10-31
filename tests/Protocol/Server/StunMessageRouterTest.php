<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\Server;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\MessageClass;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;
use Tourze\Workerman\RFC3489\Protocol\Server\StunMessageRouter;

/**
 * StunMessageRouter 测试类
 *
 * @internal
 */
#[CoversClass(StunMessageRouter::class)]
final class StunMessageRouterTest extends TestCase
{
    public function testConstructor(): void
    {
        $router = new StunMessageRouter();

        $this->assertInstanceOf(StunMessageRouter::class, $router);
    }

    public function testRegisterHandler(): void
    {
        // 创建路由器实例
        $router = new StunMessageRouter();

        // 创建模拟的处理器
        $handler = $this->createMock(StunMessageHandlerInterface::class);

        // 注册处理器
        $result = $router->registerHandler(MessageMethod::BINDING, $handler);

        // 验证返回相同的路由器实例（用于链式调用）
        $this->assertSame($router, $result);
    }

    public function testRouteMessage(): void
    {
        // 创建路由器实例
        $router = new StunMessageRouter();

        /*
         * 使用具体类 StunMessage 创建 Mock 对象（请求消息）：
         * 1) 必须使用具体类：StunMessage 是 STUN 协议消息的核心数据结构，没有相应接口
         * 2) 合理性：测试消息路由功能需要模拟具体的 STUN 请求消息类型和方法
         * 3) 替代方案：可以为 STUN 消息定义接口，但会增加协议层的抽象复杂度
         */
        $request = $this->createMock(StunMessage::class);
        $request->method('getClass')->willReturn(MessageClass::REQUEST);
        $request->method('getMethod')->willReturn(MessageMethod::BINDING);

        // 创建模拟的处理器
        $handler = $this->createMock(StunMessageHandlerInterface::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象（响应消息）：
         * 1) 必须使用具体类：StunMessage 承载 STUN 协议响应数据，用于测试消息路由的返回结果
         * 2) 合理性：测试消息路由器需要模拟处理器返回的响应消息
         * 3) 替代方案：协议消息类设计相对稳定，Mock具体类是适当的测试方式
         */
        $responseMessage = $this->createMock(StunMessage::class);
        $handler->method('handleMessage')->willReturn($responseMessage);

        // 注册处理器
        $router->registerHandler(MessageMethod::BINDING, $handler);

        // 路由消息
        $response = $router->routeMessage($request, '192.168.1.100', 12345);

        // 验证返回了响应消息
        $this->assertSame($responseMessage, $response);
    }
}
