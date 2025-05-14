<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

/**
 * 地址映射收集器
 *
 * 用于收集和分析NAT地址映射的模式，帮助确定NAT类型
 */
class AddressMappingCollector
{
    /**
     * 地址映射数组
     *
     * 格式: [
     *   [
     *     'local' => ['ip' => '本地IP', 'port' => 本地端口],
     *     'remote' => ['ip' => '远程服务器IP', 'port' => 远程服务器端口],
     *     'mapped' => ['ip' => '映射IP', 'port' => 映射端口],
     *     'timestamp' => 时间戳
     *   ],
     *   ...
     * ]
     */
    private array $mappings = [];

    /**
     * 创建一个新的地址映射收集器
     *
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(private ?LoggerInterface $logger = null)
    {
    }

    /**
     * 添加一个地址映射
     *
     * @param array $localAddress 本地地址，格式为 ['ip' => string, 'port' => int]
     * @param array $remoteAddress 远程服务器地址，格式为 ['ip' => string, 'port' => int]
     * @param array $mappedAddress 映射地址，格式为 ['ip' => string, 'port' => int]
     * @return self 当前实例，用于链式调用
     */
    public function addMapping(array $localAddress, array $remoteAddress, array $mappedAddress): self
    {
        $this->mappings[] = [
            'local' => $localAddress,
            'remote' => $remoteAddress,
            'mapped' => $mappedAddress,
            'timestamp' => microtime(true)
        ];

        $this->logInfo(sprintf(
            "添加地址映射: 本地 %s:%d -> 远程 %s:%d -> 映射 %s:%d",
            $localAddress['ip'],
            $localAddress['port'],
            $remoteAddress['ip'],
            $remoteAddress['port'],
            $mappedAddress['ip'],
            $mappedAddress['port']
        ));

        return $this;
    }

    /**
     * 添加一个规范格式的地址映射
     *
     * @param string $localIp 本地IP地址
     * @param int $localPort 本地端口
     * @param string $remoteIp 远程服务器IP地址
     * @param int $remotePort 远程服务器端口
     * @param string $mappedIp 映射IP地址
     * @param int $mappedPort 映射端口
     * @return self 当前实例，用于链式调用
     */
    public function add(
        string $localIp,
        int    $localPort,
        string $remoteIp,
        int    $remotePort,
        string $mappedIp,
        int    $mappedPort
    ): self
    {
        return $this->addMapping(
            ['ip' => $localIp, 'port' => $localPort],
            ['ip' => $remoteIp, 'port' => $remotePort],
            ['ip' => $mappedIp, 'port' => $mappedPort]
        );
    }

    /**
     * 获取所有映射
     *
     * @return array 所有映射
     */
    public function getAllMappings(): array
    {
        return $this->mappings;
    }

    /**
     * 根据本地地址获取映射
     *
     * @param string $localIp 本地IP地址
     * @param int $localPort 本地端口
     * @return array 匹配的映射列表
     */
    public function getMappingsByLocalAddress(string $localIp, int $localPort): array
    {
        return array_filter($this->mappings, function ($mapping) use ($localIp, $localPort) {
            return IpUtils::ipEquals($mapping['local']['ip'], $localIp) && $mapping['local']['port'] === $localPort;
        });
    }

    /**
     * 根据远程服务器地址获取映射
     *
     * @param string $remoteIp 远程服务器IP地址
     * @param int $remotePort 远程服务器端口
     * @return array 匹配的映射列表
     */
    public function getMappingsByRemoteAddress(string $remoteIp, int $remotePort): array
    {
        return array_filter($this->mappings, function ($mapping) use ($remoteIp, $remotePort) {
            return IpUtils::ipEquals($mapping['remote']['ip'], $remoteIp) && $mapping['remote']['port'] === $remotePort;
        });
    }

    /**
     * 根据映射地址获取映射
     *
     * @param string $mappedIp 映射IP地址
     * @param int $mappedPort 映射端口
     * @return array 匹配的映射列表
     */
    public function getMappingsByMappedAddress(string $mappedIp, int $mappedPort): array
    {
        return array_filter($this->mappings, function ($mapping) use ($mappedIp, $mappedPort) {
            return IpUtils::ipEquals($mapping['mapped']['ip'], $mappedIp) && $mapping['mapped']['port'] === $mappedPort;
        });
    }

    /**
     * 检查是否发生了地址复用
     *
     * 地址复用是指相同的本地地址映射到相同的公网地址，通常在Full Cone和Restricted Cone NAT中发生
     *
     * @return bool 如果发生了地址复用则返回true
     */
    public function hasAddressReuse(): bool
    {
        $mappedAddresses = [];

        foreach ($this->mappings as $mapping) {
            $localKey = $mapping['local']['ip'] . ':' . $mapping['local']['port'];
            $mappedKey = $mapping['mapped']['ip'] . ':' . $mapping['mapped']['port'];

            if (!isset($mappedAddresses[$localKey])) {
                $mappedAddresses[$localKey] = $mappedKey;
            } elseif ($mappedAddresses[$localKey] !== $mappedKey) {
                return false;
            }
        }

        return count($mappedAddresses) > 0;
    }

    /**
     * 检查是否发生了地址不一致
     *
     * 地址不一致是指相同的本地地址映射到不同的公网地址，通常在Symmetric NAT中发生
     *
     * @return bool 如果发生了地址不一致则返回true
     */
    public function hasAddressInconsistency(): bool
    {
        $mappedAddresses = [];

        foreach ($this->mappings as $mapping) {
            $localKey = $mapping['local']['ip'] . ':' . $mapping['local']['port'];
            $mappedKey = $mapping['mapped']['ip'] . ':' . $mapping['mapped']['port'];

            if (!isset($mappedAddresses[$localKey])) {
                $mappedAddresses[$localKey] = [];
            }

            if (!in_array($mappedKey, $mappedAddresses[$localKey])) {
                $mappedAddresses[$localKey][] = $mappedKey;
            }
        }

        foreach ($mappedAddresses as $localKey => $mappedKeys) {
            if (count($mappedKeys) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查NAT是否依赖于目标IP地址
     *
     * @return bool 如果NAT依赖于目标IP地址则返回true
     */
    public function isDependentOnDestIp(): bool
    {
        $mappings = $this->mappings;
        if (count($mappings) < 2) {
            return false;
        }

        foreach ($mappings as $i => $mapping1) {
            for ($j = $i + 1; $j < count($mappings); $j++) {
                $mapping2 = $mappings[$j];

                // 如果本地地址相同，远程IP不同，检查映射是否不同
                if (IpUtils::ipEquals($mapping1['local']['ip'], $mapping2['local']['ip']) &&
                    $mapping1['local']['port'] === $mapping2['local']['port'] &&
                    !IpUtils::ipEquals($mapping1['remote']['ip'], $mapping2['remote']['ip']) &&
                    (
                        !IpUtils::ipEquals($mapping1['mapped']['ip'], $mapping2['mapped']['ip']) ||
                        $mapping1['mapped']['port'] !== $mapping2['mapped']['port']
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 检查NAT是否依赖于目标端口
     *
     * @return bool 如果NAT依赖于目标端口则返回true
     */
    public function isDependentOnDestPort(): bool
    {
        $mappings = $this->mappings;
        if (count($mappings) < 2) {
            return false;
        }

        foreach ($mappings as $i => $mapping1) {
            for ($j = $i + 1; $j < count($mappings); $j++) {
                $mapping2 = $mappings[$j];

                // 如果本地地址相同，远程IP相同，远程端口不同，检查映射是否不同
                if (IpUtils::ipEquals($mapping1['local']['ip'], $mapping2['local']['ip']) &&
                    $mapping1['local']['port'] === $mapping2['local']['port'] &&
                    IpUtils::ipEquals($mapping1['remote']['ip'], $mapping2['remote']['ip']) &&
                    $mapping1['remote']['port'] !== $mapping2['remote']['port'] &&
                    (
                        !IpUtils::ipEquals($mapping1['mapped']['ip'], $mapping2['mapped']['ip']) ||
                        $mapping1['mapped']['port'] !== $mapping2['mapped']['port']
                    )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 推断NAT类型
     *
     * 根据收集到的地址映射推断NAT类型
     *
     * @return NatType 推断的NAT类型
     * @throws StunException 如果映射数量不足以推断NAT类型
     */
    public function inferNatType(): NatType
    {
        if (count($this->mappings) < 2) {
            throw new StunException("映射数量不足以推断NAT类型，至少需要2个映射");
        }

        // 检查第一个映射的本地和映射地址是否相同
        $firstMapping = $this->mappings[0];
        $isLocalEqualMapped = IpUtils::ipEquals($firstMapping['local']['ip'], $firstMapping['mapped']['ip']) &&
            $firstMapping['local']['port'] === $firstMapping['mapped']['port'];

        if ($isLocalEqualMapped) {
            // 如果本地地址和映射地址相同，可能是开放互联网或对称UDP防火墙
            // 这里需要额外的信息来区分
            // 默认返回开放互联网
            return NatType::OPEN_INTERNET;
        }

        // 检查是否有地址不一致
        if ($this->hasAddressInconsistency()) {
            return NatType::SYMMETRIC;
        }

        // 检查是否依赖于目标IP
        if ($this->isDependentOnDestIp()) {
            return NatType::SYMMETRIC;
        }

        // 检查是否依赖于目标端口
        if ($this->isDependentOnDestPort()) {
            return NatType::PORT_RESTRICTED_CONE;
        }

        // 默认返回完全锥形NAT
        return NatType::FULL_CONE;
    }

    /**
     * 清空所有映射
     *
     * @return self 当前实例，用于链式调用
     */
    public function clear(): self
    {
        $this->mappings = [];
        $this->logInfo("清空所有地址映射");
        return $this;
    }

    /**
     * 获取映射数量
     *
     * @return int 映射数量
     */
    public function count(): int
    {
        return count($this->mappings);
    }

    /**
     * 设置日志记录器
     *
     * @param LoggerInterface|null $logger 日志记录器
     * @return self 当前实例，用于链式调用
     */
    public function setLogger(?LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     * @return void
     */
    private function logInfo(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::INFO, "[AddressMappingCollector] $message");
        }
    }
} 