<?php

namespace Tourze\Workerman\RFC3489\Application;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Tourze\Workerman\RFC3489\Message\Constants;

/**
 * STUN应用配置类
 *
 * 提供STUN应用的配置选项
 */
class StunConfig
{
    /**
     * 绑定地址
     */
    private string $bindAddress = '0.0.0.0';

    /**
     * 绑定端口
     */
    private int $bindPort = Constants::DEFAULT_PORT;

    /**
     * 请求超时（毫秒）
     */
    private int $requestTimeout = 5000;

    /**
     * STUN服务器地址列表
     */
    private array $serverAddresses = [];

    /**
     * 调试模式
     */
    private bool $debugMode = false;

    /**
     * 获取绑定地址
     *
     * @return string 绑定地址
     */
    public function getBindAddress(): string
    {
        return $this->bindAddress;
    }

    /**
     * 设置绑定地址
     *
     * @param string $bindAddress 绑定地址
     * @return self 当前实例，用于链式调用
     */
    public function setBindAddress(string $bindAddress): self
    {
        $this->bindAddress = $bindAddress;
        return $this;
    }

    /**
     * 获取绑定端口
     *
     * @return int 绑定端口
     */
    public function getBindPort(): int
    {
        return $this->bindPort;
    }

    /**
     * 设置绑定端口
     *
     * @param int $bindPort 绑定端口
     * @return self 当前实例，用于链式调用
     */
    public function setBindPort(int $bindPort): self
    {
        $this->bindPort = $bindPort;
        return $this;
    }

    /**
     * 获取请求超时
     *
     * @return int 请求超时（毫秒）
     */
    public function getRequestTimeout(): int
    {
        return $this->requestTimeout;
    }

    /**
     * 设置请求超时
     *
     * @param int $requestTimeout 请求超时（毫秒）
     * @return self 当前实例，用于链式调用
     */
    public function setRequestTimeout(int $requestTimeout): self
    {
        $this->requestTimeout = $requestTimeout;
        return $this;
    }

    /**
     * 获取服务器地址列表
     *
     * @return array 服务器地址列表，每个元素为 [地址, 端口] 的数组
     */
    public function getServerAddresses(): array
    {
        return $this->serverAddresses;
    }

    /**
     * 添加服务器地址
     *
     * @param string $address 服务器地址
     * @param int $port 服务器端口
     * @return self 当前实例，用于链式调用
     */
    public function addServerAddress(string $address, int $port): self
    {
        $this->serverAddresses[] = [$address, $port];
        return $this;
    }

    /**
     * 检查是否为调试模式
     *
     * @return bool 如果是调试模式则返回true
     */
    public function isDebugMode(): bool
    {
        return $this->debugMode;
    }

    /**
     * 设置调试模式
     *
     * @param bool $debugMode 是否启用调试模式
     * @return self 当前实例，用于链式调用
     */
    public function setDebugMode(bool $debugMode): self
    {
        $this->debugMode = $debugMode;
        return $this;
    }

    /**
     * 从数组创建配置
     *
     * @param array $config 配置数组
     * @return self 新的配置实例
     */
    public static function fromArray(array $config): self
    {
        $instance = new self();

        if (isset($config['bindAddress'])) {
            $instance->setBindAddress($config['bindAddress']);
        }

        if (isset($config['bindPort'])) {
            $instance->setBindPort($config['bindPort']);
        }

        if (isset($config['requestTimeout'])) {
            $instance->setRequestTimeout($config['requestTimeout']);
        }

        if (isset($config['debugMode'])) {
            $instance->setDebugMode($config['debugMode']);
        }

        if (isset($config['serverAddresses']) && is_array($config['serverAddresses'])) {
            foreach ($config['serverAddresses'] as $server) {
                if (is_array($server) && count($server) >= 2) {
                    $instance->addServerAddress($server[0], $server[1]);
                }
            }
        }

        return $instance;
    }

    /**
     * 将配置转换为数组
     *
     * @return array 配置数组
     */
    public function toArray(): array
    {
        return [
            'bindAddress' => $this->bindAddress,
            'bindPort' => $this->bindPort,
            'requestTimeout' => $this->requestTimeout,
            'serverAddresses' => $this->serverAddresses,
            'debugMode' => $this->debugMode,
        ];
    }

    /**
     * 从YAML字符串创建配置
     *
     * @param string $yaml YAML字符串
     * @return self 新的配置实例
     * @throws \RuntimeException 如果无法解析YAML
     */
    public static function fromYaml(string $yaml): self
    {
        try {
            $config = Yaml::parse($yaml);
            return self::fromArray($config);
        } catch (ParseException $e) {
            throw new \RuntimeException("无法解析YAML配置: " . $e->getMessage());
        }
    }

    /**
     * 将配置转换为YAML字符串
     *
     * @return string YAML字符串
     */
    public function toYaml(): string
    {
        return Yaml::dump($this->toArray(), 3, 2);
    }
}
