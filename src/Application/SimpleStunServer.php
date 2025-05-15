<?php

namespace Tourze\Workerman\RFC3489\Application;

use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServer;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerFactory;

/**
 * 简单的STUN服务器应用
 *
 * 提供易用的STUN服务器功能，实现RFC3489标准
 */
class SimpleStunServer extends StunApplication
{
    /**
     * STUN服务器实例
     */
    private ?StunServer $server = null;

    /**
     * 启动STUN服务器应用
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     * @throws StunException 如果启动失败
     */
    public function start(bool $daemon = false): void
    {
        if ($this->running) {
            $this->logWarning("服务器已经在运行");
            return;
        }

        $this->logInfo("初始化STUN服务器");

        // 获取配置
        $bindIp = $this->config->get('server.bind_ip');
        $bindPort = $this->config->get('server.bind_port');
        $alternateIp = $this->config->get('server.alternate_ip');
        $alternatePort = $this->config->get('server.alternate_port') ?: ($bindPort + 1);

        try {
            // 创建STUN服务器
            $authHandler = $this->config->get('server.auth_enabled', false) ? [$this, 'handleAuth'] : null;

            $this->server = StunServerFactory::create(
                $bindIp,
                $bindPort,
                $alternateIp,
                $alternatePort,
                $authHandler,
                $this->logger
            );

            $this->running = true;

            $this->logInfo("正在启动STUN服务器，监听 {$bindIp}:{$bindPort}");

            // 如果配置了守护进程模式，或者参数指定了守护进程模式
            $useDaemon = $this->config->get('server.daemon', false) || $daemon;

            $this->server->start($useDaemon);

        } catch (StunException $e) {
            $this->running = false;
            $this->logError("启动STUN服务器失败: " . $e->getMessage());
            throw $e;
        } catch (\Throwable $e) {
            $this->running = false;
            $this->logError("启动STUN服务器时发生未知错误: " . $e->getMessage());
            throw new StunException("启动服务器失败", 0, $e);
        }
    }

    /**
     * 停止STUN服务器应用
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->logInfo("停止STUN服务器");

        if ($this->server !== null) {
            $this->server->stop();
            $this->server = null;
        }

        $this->running = false;
    }

    /**
     * 认证处理器
     *
     * @param object $request STUN请求消息
     * @param string $clientIp 客户端IP地址
     * @param int $clientPort 客户端端口
     * @return bool|string 认证成功返回true，失败返回错误消息
     */
    public function handleAuth(object $request, string $clientIp, int $clientPort)
    {
        // 获取允许的IP列表
        $allowedIps = $this->config->get('server.allowed_ips', []);

        // 如果没有设置允许的IP列表，则允许所有IP
        if (empty($allowedIps)) {
            return true;
        }

        // 检查客户端IP是否在允许列表中
        foreach ($allowedIps as $allowedIp) {
            // 支持IP段匹配（简单实现）
            if (strpos($allowedIp, '/') !== false) {
                [$network, $subnet] = explode('/', $allowedIp);
                if ($this->ipInRange($clientIp, $network, $subnet)) {
                    return true;
                }
            } elseif ($clientIp === $allowedIp) {
                return true;
            }
        }

        $this->logWarning("拒绝来自 {$clientIp}:{$clientPort} 的请求：IP不在允许列表中");
        return "IP不在允许列表中";
    }

    /**
     * 检查IP是否在指定范围内
     *
     * @param string $ip 要检查的IP
     * @param string $network 网络地址
     * @param string $subnet 子网掩码或CIDR前缀
     * @return bool 如果IP在范围内则返回true
     */
    private function ipInRange(string $ip, string $network, string $subnet): bool
    {
        // 将IP转换为无符号长整型
        $ipLong = ip2long($ip);
        $networkLong = ip2long($network);

        // 子网掩码可以是前缀长度（如24）或子网掩码（如255.255.255.0）
        if (is_numeric($subnet)) {
            // CIDR前缀
            $mask = -1 << (32 - (int)$subnet);
        } else {
            // 子网掩码
            $mask = ip2long($subnet);
        }

        // 检查IP是否在网络范围内
        return ($ipLong & $mask) === ($networkLong & $mask);
    }

    /**
     * 获取STUN服务器实例
     *
     * @return StunServer|null STUN服务器实例，如果未初始化则返回null
     */
    public function getServer(): ?StunServer
    {
        return $this->server;
    }
}
