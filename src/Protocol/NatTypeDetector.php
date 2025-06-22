<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
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
     * STUN服务器地址
     */
    private string $serverAddress;

    /**
     * STUN服务器端口
     */
    private int $serverPort;

    /**
     * STUN测试执行器
     */
    private StunTestExecutor $testExecutor;

    /**
     * STUN请求发送器
     */
    private StunRequestSender $requestSender;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 创建一个新的NAT类型检测器
     *
     * @param string $serverAddress STUN服务器地址
     * @param int $serverPort STUN服务器端口，默认为3478
     * @param StunTransport|null $transport 传输接口，如果为null则创建默认的UDP传输
     * @param int $timeout 超时时间（毫秒）
     * @param LoggerInterface|null $logger 日志记录器
     */
    public function __construct(
        string $serverAddress,
        int $serverPort = Constants::DEFAULT_PORT,
        ?StunTransport $transport = null,
        int $timeout = 5000,
        ?LoggerInterface $logger = null
    ) {
        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
        $this->logger = $logger;

        if ($transport === null) {
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
     * @throws StunException 如果检测过程中发生错误
     */
    public function detect(): NatType
    {
        try {
            $this->logInfo("开始NAT类型检测...");

            // 测试I: 向服务器发送绑定请求
            $test1Result = $this->testExecutor->performTest1($this->serverAddress, $this->serverPort);

            if ($test1Result === null) {
                $this->logInfo("测试I失败，NAT类型: 阻塞");
                return NatType::BLOCKED;
            }

            [$mappedAddress, $changedAddress] = $test1Result;
            $mappedIp = $mappedAddress->getIp();
            $mappedPort = $mappedAddress->getPort();

            $this->logInfo("测试I成功，映射地址: $mappedIp:$mappedPort");

            // 获取本地地址
            $localAddress = $this->requestSender->getTransport()->getLocalAddress();
            if ($localAddress === null) {
                $this->logWarning("无法获取本地地址，假设NAT类型: 对称NAT");
                return NatType::SYMMETRIC;
            }

            [$localIp, $localPort] = $localAddress;
            $this->logInfo("本地地址: $localIp:$localPort");

            // 检查映射地址是否与本地地址匹配
            $isMappedAddressMatchesLocal = IpUtils::ipEquals($mappedIp, $localIp) && $mappedPort === $localPort;

            if ($isMappedAddressMatchesLocal) {
                $this->logInfo("映射地址与本地地址匹配");

                // 测试II: 向变更地址发送绑定请求
                $isTest2Success = $this->testExecutor->performTest2($changedAddress, $this->serverAddress);

                if ($isTest2Success) {
                    $this->logInfo("测试II成功，NAT类型: 开放互联网");
                    return NatType::OPEN_INTERNET;
                } else {
                    $this->logInfo("测试II失败，NAT类型: 对称UDP防火墙");
                    return NatType::SYMMETRIC_UDP_FIREWALL;
                }
            } else {
                $this->logInfo("映射地址与本地地址不匹配，存在NAT");

                // 测试II: 向变更地址发送绑定请求
                $isTest2Success = $this->testExecutor->performTest2($changedAddress, $this->serverAddress);

                if ($isTest2Success) {
                    $this->logInfo("测试II成功，NAT类型: 完全锥形NAT");
                    return NatType::FULL_CONE;
                } else {
                    // 测试I(改变IP和端口): 向初始服务器发送绑定请求，要求改变IP和端口
                    $mappedAddress2 = $this->testExecutor->performTest1WithChangeRequest(
                        $this->serverAddress,
                        $this->serverPort,
                        true,
                        true
                    );

                    if ($mappedAddress2 === null) {
                        $this->logInfo("测试I(改变IP和端口)失败，尝试只改变IP的请求");
                        // 尝试只改变IP的请求
                        $mappedAddress2 = $this->testExecutor->performTest1WithChangeRequest(
                            $this->serverAddress,
                            $this->serverPort,
                            true,
                            false
                        );

                        if ($mappedAddress2 === null) {
                            $this->logWarning("无法执行改变IP的请求，假设NAT类型: 端口受限锥形NAT");
                            return NatType::PORT_RESTRICTED_CONE;
                        }
                    }

                    // 检查两次映射地址是否相同
                    $mappedIp2 = $mappedAddress2->getIp();
                    $mappedPort2 = $mappedAddress2->getPort();

                    $this->logInfo("测试I(改变IP和端口)成功，新映射地址: $mappedIp2:$mappedPort2");

                    $isMappedAddressChanged = !IpUtils::ipEquals($mappedIp, $mappedIp2) || $mappedPort !== $mappedPort2;

                    if ($isMappedAddressChanged) {
                        $this->logInfo("映射地址改变，NAT类型: 对称NAT");
                        return NatType::SYMMETRIC;
                    } else {
                        // 测试III: 向初始服务器发送绑定请求，要求改变端口
                        $isTest3Success = $this->testExecutor->performTest3($this->serverAddress, $this->serverPort);

                        if ($isTest3Success) {
                            $this->logInfo("测试III成功，NAT类型: 受限锥形NAT");
                            return NatType::RESTRICTED_CONE;
                        } else {
                            $this->logInfo("测试III失败，NAT类型: 端口受限锥形NAT");
                            return NatType::PORT_RESTRICTED_CONE;
                        }
                    }
                }
            }
        } catch (TimeoutException $e) {
            $this->logError("检测超时: " . $e->getMessage());
            return NatType::UNKNOWN;
        } catch (TransportException $e) {
            $this->logError("传输错误: " . $e->getMessage());
            return NatType::UNKNOWN;
        } catch (\Throwable $e) {
            $this->logError("检测过程中发生错误: " . $e->getMessage());
            throw new StunException("NAT类型检测失败: " . $e->getMessage(), 0, $e);
        } finally {
            // 确保关闭传输连接
            $this->requestSender->close();
        }
    }



    /**
     * 记录信息日志
     *
     * @param string $message 日志消息
     */
    private function logInfo(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::INFO, "[NatDetector] $message");
        }
    }

    /**
     * 记录警告日志
     *
     * @param string $message 日志消息
     */
    private function logWarning(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::WARNING, "[NatDetector] $message");
        }
    }

    /**
     * 记录错误日志
     *
     * @param string $message 日志消息
     */
    private function logError(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::ERROR, "[NatDetector] $message");
        }
    }
}
