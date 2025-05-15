<?php

namespace Tourze\Workerman\RFC3489\Application;

use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Protocol\NatType;
use Tourze\Workerman\RFC3489\Protocol\NatTypeDetector;
use Tourze\Workerman\RFC3489\Protocol\StunClient;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;

/**
 * 简单的STUN客户端应用
 *
 * 提供易用的STUN客户端功能，包括NAT类型检测和公网地址发现
 */
class SimpleStunClient extends StunApplication
{
    /**
     * STUN客户端实例
     */
    private ?StunClient $client = null;

    /**
     * NAT类型检测器实例
     */
    private ?NatTypeDetector $natDetector = null;

    /**
     * 启动STUN客户端应用
     *
     * 由于客户端通常是按需执行，此方法仅初始化客户端，不会进入循环
     *
     * @param bool $daemon 是否以守护进程方式运行
     * @return void
     * @throws StunException 如果启动失败
     */
    public function start(bool $daemon = false): void
    {
        if ($this->running) {
            $this->logWarning("客户端已经在运行");
            return;
        }

        $this->logInfo("初始化STUN客户端");

        // 创建传输配置
        $transportConfig = new TransportConfig(
            $this->config->get('transport.local_ip'),
            $this->config->get('transport.local_port')
        );

        // 设置套接字选项
        $transportConfig->setSocketTimeout($this->config->get('transport.socket_timeout'));
        $transportConfig->setSocketBufferSize($this->config->get('transport.socket_buffer_size'));

        // 创建传输实例
        $transport = new UdpTransport($transportConfig, $this->logger);

        // 创建STUN客户端
        $this->client = new StunClient(
            $this->config->get('client.server_address'),
            $this->config->get('client.server_port'),
            $transport,
            $this->config->get('client.timeout'),
            $this->logger
        );

        // 创建NAT类型检测器
        $this->natDetector = new NatTypeDetector(
            $this->config->get('client.server_address'),
            $this->config->get('client.server_port'),
            null,
            $this->config->get('client.timeout'),
            $this->logger
        );

        $this->running = true;
        $this->logInfo("STUN客户端初始化完成");
    }

    /**
     * 停止STUN客户端应用
     *
     * @return void
     */
    public function stop(): void
    {
        if (!$this->running) {
            return;
        }

        $this->logInfo("停止STUN客户端");

        if ($this->client !== null) {
            $this->client->close();
            $this->client = null;
        }

        $this->running = false;
    }

    /**
     * 获取公网IP地址和端口
     *
     * @return array 包含公网IP地址和端口的数组，格式为 [string $ip, int $port]
     * @throws StunException 如果获取失败
     */
    public function getPublicAddress(): array
    {
        if (!$this->running) {
            $this->start();
        }

        if ($this->client === null) {
            throw new StunException("STUN客户端未初始化");
        }

        $this->logInfo("正在获取公网地址...");

        try {
            $result = $this->client->getMappedAddress();

            $this->logInfo("获取公网地址成功: {$result[0]}:{$result[1]}");

            return $result;
        } catch (StunException $e) {
            $this->logError("获取公网地址失败: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 检测NAT类型
     *
     * @return NatType 检测到的NAT类型
     * @throws StunException 如果检测失败
     */
    public function detectNatType(): NatType
    {
        if (!$this->running) {
            $this->start();
        }

        if ($this->natDetector === null) {
            throw new StunException("NAT类型检测器未初始化");
        }

        $this->logInfo("正在检测NAT类型...");

        try {
            $natType = $this->natDetector->detect();

            $this->logInfo("NAT类型检测成功: " . $natType->value);

            return $natType;
        } catch (StunException $e) {
            $this->logError("NAT类型检测失败: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取NAT信息
     *
     * @return array NAT信息，包含类型、描述、公网地址等
     * @throws StunException 如果获取失败
     */
    public function getNatInfo(): array
    {
        if (!$this->running) {
            $this->start();
        }

        try {
            // 获取NAT类型
            $natType = $this->detectNatType();

            // 获取公网地址
            $publicAddress = $this->getPublicAddress();

            return [
                'nat_type' => $natType->value,
                'description' => $natType->getDescription(),
                'public_ip' => $publicAddress[0],
                'public_port' => $publicAddress[1],
                'support_p2p' => $natType->isSupportP2P(),
                'p2p_advice' => $natType->getP2PAdvice(),
            ];
        } catch (StunException $e) {
            $this->logError("获取NAT信息失败: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取STUN客户端实例
     *
     * @return StunClient|null STUN客户端实例，如果未初始化则返回null
     */
    public function getClient(): ?StunClient
    {
        return $this->client;
    }

    /**
     * 获取NAT类型检测器实例
     *
     * @return NatTypeDetector|null NAT类型检测器实例，如果未初始化则返回null
     */
    public function getNatDetector(): ?NatTypeDetector
    {
        return $this->natDetector;
    }
}
