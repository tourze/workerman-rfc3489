<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunTestExecutor;
use Tourze\Workerman\RFC3489\Transport\StunTransport;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

/**
 * NAT类型检测器
 *
 * 实现RFC3489中定义的NAT类型检测算法
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
 */
class NatTypeDetector
{
    /**
     * STUN测试执行器
     */
    private StunTestExecutor $testExecutor;

    /**
     * STUN请求发送器
     */
    private StunRequestSender $requestSender;

    /**
     * 创建一个新的NAT类型检测器
     *
     * @param string               $serverAddress STUN服务器地址
     * @param int                  $serverPort    STUN服务器端口，默认为3478
     * @param StunTransport|null   $transport     传输接口，如果为null则创建默认的UDP传输
     * @param int                  $timeout       超时时间（毫秒）
     * @param LoggerInterface|null $logger        日志记录器
     */
    public function __construct(
        private readonly string $serverAddress,
        private readonly int $serverPort = Constants::DEFAULT_PORT,
        ?StunTransport $transport = null,
        int $timeout = 5000,
        private readonly ?LoggerInterface $logger = null,
    ) {
        if (null === $transport) {
            $config = new TransportConfig();
            $transport = new UdpTransport($config, $logger);
        }

        $this->requestSender = new StunRequestSender($transport, $timeout, $logger);
        $this->testExecutor = new StunTestExecutor($this->requestSender);
    }

    /**
     * 检测NAT类型
     *
     * 实现RFC3489中10.1节定义的NAT类型检测算法
     *
     * @return NatType 检测到的NAT类型
     *
     * @throws ProtocolException 如果检测过程中发生错误
     * @throws TimeoutException 如果请求超时
     * @throws TransportException 如果传输层发生错误
     */
    public function detect(): NatType
    {
        try {
            $this->logInfo('开始NAT类型检测...');

            $test1Result = $this->performInitialTest();
            if (null === $test1Result) {
                return NatType::BLOCKED;
            }

            [$mappedAddress, $changedAddress] = $test1Result;
            $localAddress = $this->getLocalAddress();

            if (null === $localAddress) {
                return NatType::SYMMETRIC;
            }

            if ($this->isLocalAddressMatchesMapped($localAddress, $mappedAddress)) {
                return $this->detectPublicInternet($changedAddress);
            }

            return $this->detectNatBehavior($mappedAddress, $changedAddress);
        } catch (TimeoutException $e) {
            $this->logError('检测超时: ' . $e->getMessage());

            return NatType::UNKNOWN;
        } catch (TransportException $e) {
            $this->logError('传输错误: ' . $e->getMessage());

            return NatType::UNKNOWN;
        } catch (\Throwable $e) {
            $this->logError('检测过程中发生错误: ' . $e->getMessage());
            throw ProtocolException::messageHandlingFailed('NAT检测', $e->getMessage());
        } finally {
            // 确保关闭传输连接
            $this->requestSender->close();
        }
    }

    /**
     * 执行初始测试（测试I）
     *
     * @return array{0: MappedAddress, 1: ChangedAddress}|null
     */
    private function performInitialTest(): ?array
    {
        $test1Result = $this->testExecutor->performTest1($this->serverAddress, $this->serverPort);

        if (null === $test1Result) {
            $this->logInfo('测试I失败，NAT类型: 阻塞');

            return null;
        }

        [$mappedAddress, $changedAddress] = $test1Result;
        $mappedIp = $mappedAddress->getIp();
        $mappedPort = $mappedAddress->getPort();
        $this->logInfo("测试I成功，映射地址: {$mappedIp}:{$mappedPort}");

        return $test1Result;
    }

    /**
     * 获取本地地址
     *
     * @return array{0: string, 1: int}|null
     */
    private function getLocalAddress(): ?array
    {
        $localAddress = $this->requestSender->getTransport()->getLocalAddress();
        if (null === $localAddress) {
            $this->logWarning('无法获取本地地址，假设NAT类型: 对称NAT');

            return null;
        }

        [$localIp, $localPort] = $localAddress;
        $this->logInfo("本地地址: {$localIp}:{$localPort}");

        return $localAddress;
    }

    /**
     * 检查本地地址是否与映射地址匹配
     *
     * @param array{0: string, 1: int} $localAddress
     */
    private function isLocalAddressMatchesMapped(array $localAddress, MappedAddress $mappedAddress): bool
    {
        [$localIp, $localPort] = $localAddress;

        return IpUtils::ipEquals($mappedAddress->getIp(), $localIp)
            && $mappedAddress->getPort() === $localPort;
    }

    /**
     * 检测公共互联网情况
     */
    private function detectPublicInternet(ChangedAddress $changedAddress): NatType
    {
        $this->logInfo('映射地址与本地地址匹配');

        $isTest2Success = $this->testExecutor->performTest2($changedAddress, $this->serverAddress);

        if ($isTest2Success) {
            $this->logInfo('测试II成功，NAT类型: 开放互联网');

            return NatType::OPEN_INTERNET;
        }

        $this->logInfo('测试II失败，NAT类型: 对称UDP防火墙');

        return NatType::SYMMETRIC_UDP_FIREWALL;
    }

    /**
     * 检测NAT行为
     */
    private function detectNatBehavior(MappedAddress $mappedAddress, ChangedAddress $changedAddress): NatType
    {
        $this->logInfo('映射地址与本地地址不匹配，存在NAT');

        $isTest2Success = $this->testExecutor->performTest2($changedAddress, $this->serverAddress);

        if ($isTest2Success) {
            $this->logInfo('测试II成功，NAT类型: 完全锥形NAT');

            return NatType::FULL_CONE;
        }

        return $this->performAdvancedNatDetection($mappedAddress);
    }

    /**
     * 执行高级NAT检测
     */
    private function performAdvancedNatDetection(MappedAddress $mappedAddress): NatType
    {
        $mappedAddress2 = $this->performTestWithChangeRequest();

        if (null === $mappedAddress2) {
            $this->logWarning('无法执行改变IP的请求，假设NAT类型: 端口受限锥形NAT');

            return NatType::PORT_RESTRICTED_CONE;
        }

        if ($this->isMappedAddressChanged($mappedAddress, $mappedAddress2)) {
            $this->logInfo('映射地址改变，NAT类型: 对称NAT');

            return NatType::SYMMETRIC;
        }

        return $this->performFinalTest();
    }

    /**
     * 执行带变更请求的测试
     */
    private function performTestWithChangeRequest(): ?MappedAddress
    {
        $mappedAddress2 = $this->testExecutor->performTest1WithChangeRequest(
            $this->serverAddress,
            $this->serverPort,
            true,
            true
        );

        if (null === $mappedAddress2) {
            $this->logInfo('测试I(改变IP和端口)失败，尝试只改变IP的请求');
            $mappedAddress2 = $this->testExecutor->performTest1WithChangeRequest(
                $this->serverAddress,
                $this->serverPort,
                true,
                false
            );
        }

        if (null !== $mappedAddress2) {
            $mappedIp2 = $mappedAddress2->getIp();
            $mappedPort2 = $mappedAddress2->getPort();
            $this->logInfo("测试I(改变IP和端口)成功，新映射地址: {$mappedIp2}:{$mappedPort2}");
        }

        return $mappedAddress2;
    }

    /**
     * 检查映射地址是否发生变化
     */
    private function isMappedAddressChanged(MappedAddress $mappedAddress1, MappedAddress $mappedAddress2): bool
    {
        return !IpUtils::ipEquals($mappedAddress1->getIp(), $mappedAddress2->getIp())
            || $mappedAddress1->getPort() !== $mappedAddress2->getPort();
    }

    /**
     * 执行最终测试
     */
    private function performFinalTest(): NatType
    {
        $isTest3Success = $this->testExecutor->performTest3($this->serverAddress, $this->serverPort);

        if ($isTest3Success) {
            $this->logInfo('测试III成功，NAT类型: 受限锥形NAT');

            return NatType::RESTRICTED_CONE;
        }

        $this->logInfo('测试III失败，NAT类型: 端口受限锥形NAT');

        return NatType::PORT_RESTRICTED_CONE;
    }

    /**
     * 记录信息日志
     *
     * @param string $message 日志消息
     */
    private function logInfo(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::INFO, "[NatDetector] {$message}");
        }
    }

    /**
     * 记录警告日志
     *
     * @param string $message 日志消息
     */
    private function logWarning(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::WARNING, "[NatDetector] {$message}");
        }
    }

    /**
     * 记录错误日志
     *
     * @param string $message 日志消息
     */
    private function logError(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::ERROR, "[NatDetector] {$message}");
        }
    }
}
