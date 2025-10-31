<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server;

use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\BindingRequestHandler;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\SharedSecretRequestHandler;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;

/**
 * STUN服务器工厂
 *
 * 用于创建和配置STUN服务器及其组件
 */
class StunServerFactory
{
    /**
     * 创建一个标准配置的STUN服务器
     *
     * @param string               $bindIp        服务器绑定IP地址
     * @param int                  $bindPort      服务器绑定端口
     * @param string               $alternateIp   备用IP地址
     * @param int                  $alternatePort 备用端口
     * @param callable|null        $authHandler   认证处理器
     * @param LoggerInterface|null $logger        日志记录器
     *
     * @return StunServer 配置好的STUN服务器实例
     */
    public static function create(
        string $bindIp = '0.0.0.0',
        int $bindPort = Constants::DEFAULT_PORT,
        string $alternateIp = '0.0.0.0',
        int $alternatePort = 0,
        ?callable $authHandler = null,
        ?LoggerInterface $logger = null,
    ): StunServer {
        // 创建传输层
        $config = new TransportConfig();
        $transport = new UdpTransport($config, $logger);

        // 创建消息路由器
        $messageRouter = new StunMessageRouter($logger);

        // 创建请求处理器
        $alternatePort = 0 !== $alternatePort ? $alternatePort : ($bindPort + 1);
        $bindingHandler = new BindingRequestHandler($transport, $alternateIp, $alternatePort, $authHandler, $logger);
        $sharedSecretHandler = new SharedSecretRequestHandler($logger);

        // 注册处理器
        $messageRouter->registerHandler(MessageMethod::BINDING, $bindingHandler);
        $messageRouter->registerHandler(MessageMethod::SHARED_SECRET, $sharedSecretHandler);

        // 创建适配器
        $standaloneAdapter = new StunServerStandaloneAdapter(
            $transport,
            $bindIp,
            $bindPort,
            $messageRouter,
            $logger
        );

        // 创建并返回服务器实例
        return new StunServer(
            $bindIp,
            $bindPort,
            $alternateIp,
            $alternatePort,
            $transport,
            $messageRouter,
            $standaloneAdapter,
            $logger
        );
    }
}
