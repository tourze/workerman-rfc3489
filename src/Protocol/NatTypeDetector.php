<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangeRequest;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\StunMessage;
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
     * STUN传输接口
     */
    private StunTransport $transport;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 检测超时时间（毫秒）
     */
    private int $timeout;

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
        $this->timeout = $timeout;
        $this->logger = $logger;

        if ($transport === null) {
            $config = new TransportConfig();
            $transport = new UdpTransport($config, $logger);
        }

        $this->transport = $transport;
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
            $this->logInfo("测试I: 向服务器发送绑定请求");
            $test1Result = $this->performTest1();

            if ($test1Result === null) {
                $this->logInfo("测试I失败，NAT类型: 阻塞");
                return NatType::BLOCKED;
            }

            [$mappedAddress, $changedAddress] = $test1Result;
            $mappedIp = $mappedAddress->getIp();
            $mappedPort = $mappedAddress->getPort();

            $this->logInfo("测试I成功，映射地址: $mappedIp:$mappedPort");

            // 获取本地地址
            $localAddress = $this->transport->getLocalAddress();
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
                $this->logInfo("测试II: 向变更地址发送绑定请求");
                $isTest2Success = $this->performTest2($changedAddress);

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
                $this->logInfo("测试II: 向变更地址发送绑定请求");
                $isTest2Success = $this->performTest2($changedAddress);

                if ($isTest2Success) {
                    $this->logInfo("测试II成功，NAT类型: 完全锥形NAT");
                    return NatType::FULL_CONE;
                } else {
                    // 测试I(改变IP和端口): 向初始服务器发送绑定请求，要求改变IP和端口
                    $this->logInfo("测试I(改变IP和端口): 向初始服务器发送绑定请求，要求改变IP和端口");
                    $mappedAddress2 = $this->performTest1WithChangeRequest(true, true);

                    if ($mappedAddress2 === null) {
                        $this->logInfo("测试I(改变IP和端口)失败，尝试只改变IP的请求");
                        // 尝试只改变IP的请求
                        $mappedAddress2 = $this->performTest1WithChangeRequest(true, false);

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
                        $this->logInfo("测试III: 向初始服务器发送绑定请求，要求改变端口");
                        $isTest3Success = $this->performTest3();

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
            try {
                $this->transport->close();
            } catch (\Throwable $e) {
                $this->logWarning("关闭传输连接时发生错误: " . $e->getMessage());
            }
        }
    }

    /**
     * 执行测试I：向服务器发送基本的绑定请求
     *
     * @return array|null 如果成功则返回 [MappedAddress, ChangedAddress]，否则返回null
     * @throws TimeoutException 如果请求超时
     * @throws TransportException 如果传输过程中发生错误
     */
    private function performTest1(): ?array
    {
        // 创建绑定请求
        $request = MessageFactory::createBindingRequest();

        // 发送请求并等待响应
        $response = $this->sendRequest($request, $this->serverAddress, $this->serverPort);

        if ($response === null) {
            return null;
        }

        // 获取MAPPED-ADDRESS属性
        $mappedAddress = $response->getAttribute(AttributeType::MAPPED_ADDRESS);
        if ($mappedAddress === null) {
            $this->logWarning("响应中缺少MAPPED-ADDRESS属性");
            return null;
        }

        // 获取CHANGED-ADDRESS属性
        $changedAddress = $response->getAttribute(AttributeType::CHANGED_ADDRESS);
        if ($changedAddress === null) {
            $this->logWarning("响应中缺少CHANGED-ADDRESS属性");
            return null;
        }

        return [$mappedAddress, $changedAddress];
    }

    /**
     * 执行测试I，添加CHANGE-REQUEST属性
     *
     * @param bool $changeIp 是否改变IP
     * @param bool $changePort 是否改变端口
     * @return MappedAddress|null 如果成功则返回MappedAddress，否则返回null
     * @throws TimeoutException 如果请求超时
     * @throws TransportException 如果传输过程中发生错误
     */
    private function performTest1WithChangeRequest(bool $changeIp, bool $changePort): ?MappedAddress
    {
        // 创建绑定请求，附带Change Request属性
        $request = MessageFactory::createBindingRequest();
        $changeRequest = new ChangeRequest($changeIp, $changePort);
        $request->addAttribute($changeRequest);
        
        // 日志信息
        $changeDesc = [];
        if ($changeIp) $changeDesc[] = "IP";
        if ($changePort) $changeDesc[] = "端口";
        $changeDescStr = !empty($changeDesc) ? implode("和", $changeDesc) : "无";
        
        $this->logInfo("测试I(改变{$changeDescStr}): 向初始服务器发送绑定请求，要求改变{$changeDescStr}");
        
        // 发送请求到初始服务器
        $response = $this->sendRequest($request, $this->serverAddress, $this->serverPort);
        
        if ($response === null) {
            $this->logWarning("测试I(改变{$changeDescStr})失败: 未收到响应");
            return null;
        }
        
        // 获取映射地址属性
        $mappedAddress = $response->getAttribute(AttributeType::MAPPED_ADDRESS);
        if ($mappedAddress === null) {
            $this->logWarning("测试I(改变{$changeDescStr})失败: 响应中没有映射地址");
            return null;
        }
        
        $this->logInfo("测试I(改变{$changeDescStr})成功，新映射地址: {$mappedAddress->getIp()}:{$mappedAddress->getPort()}");
        
        return $mappedAddress;
    }

    /**
     * 执行测试II：向变更地址发送绑定请求
     *
     * @param ChangedAddress $changedAddress 变更地址
     * @return bool 如果成功收到响应则返回true
     */
    private function performTest2(ChangedAddress $changedAddress): bool
    {
        $changedIp = $changedAddress->getIp();
        $changedPort = $changedAddress->getPort();
        
        // 修复: 如果变更地址IP为0.0.0.0，则使用原始服务器IP，只改变端口
        if ($changedIp === '0.0.0.0') {
            $this->logWarning("服务器返回的变更IP无效(0.0.0.0)，将使用原始服务器IP");
            $changedIp = $this->serverAddress;
        }
        
        $this->logInfo("测试II: 向变更地址发送绑定请求");
        
        // 创建绑定请求
        $request = MessageFactory::createBindingRequest();
        
        // 发送请求到变更地址
        $response = $this->sendRequest($request, $changedIp, $changedPort);
        
        if ($response === null) {
            $this->logWarning("测试II失败: 未收到响应");
            return false;
        }
        
        // 获取映射地址属性
        $mappedAddress = $response->getAttribute(AttributeType::MAPPED_ADDRESS);
        if ($mappedAddress === null) {
            $this->logWarning("测试II失败: 响应中没有映射地址");
            return false;
        }
        
        return true;
    }

    /**
     * 执行测试III：向初始服务器发送绑定请求，要求改变端口
     *
     * @return bool 如果成功收到响应则返回true
     */
    private function performTest3(): bool
    {
        // 创建绑定请求
        $request = MessageFactory::createBindingRequest();

        // 添加CHANGE-REQUEST属性，只改变端口
        $changeRequest = new ChangeRequest(false, true);
        $request->addAttribute($changeRequest);

        try {
            // 发送请求并等待响应
            $response = $this->sendRequest($request, $this->serverAddress, $this->serverPort);
            return $response !== null;
        } catch (\Throwable $e) {
            $this->logWarning("测试III失败: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 发送STUN请求并等待响应
     *
     * @param StunMessage $request 请求消息
     * @param string $destIp 目标IP地址
     * @param int $destPort 目标端口
     * @return StunMessage|null 如果成功则返回响应消息，否则返回null
     * @throws TimeoutException 如果请求超时
     * @throws TransportException 如果传输过程中发生错误
     */
    private function sendRequest(StunMessage $request, string $destIp, int $destPort): ?StunMessage
    {
        $this->logDebug("发送请求到 $destIp:$destPort");

        $startTime = microtime(true);

        // 解析域名为IP地址（如果是域名的话）
        $resolvedDestIp = $destIp;
        if (!filter_var($destIp, FILTER_VALIDATE_IP)) {
            $ips = gethostbynamel($destIp);
            if ($ips && !empty($ips)) {
                $resolvedDestIp = $ips[0];
                $this->logDebug("将域名 $destIp 解析为IP地址 $resolvedDestIp");
            }
        }

        // 检查目标IP是否为0.0.0.0，这是一个无效的目标地址
        if ($resolvedDestIp === '0.0.0.0') {
            $this->logWarning("目标IP为0.0.0.0，这是一个无效的目标地址");
            return null;
        }

        // 确保传输已关闭并重新绑定
        $this->transport->close();
        if (!$this->transport->bind('0.0.0.0', 0)) {
            throw new TransportException("无法绑定传输");
        }

        // 发送请求
        if (!$this->transport->send($request, $destIp, $destPort)) {
            $error = $this->transport->getLastError() ?? "未知错误";
            throw new TransportException("发送请求失败: $error");
        }

        // 等待响应
        while (true) {
            // 检查是否超时
            if ((microtime(true) - $startTime) * 1000 > $this->timeout) {
                throw new TimeoutException("等待响应超时");
            }

            // 尝试接收响应
            $result = $this->transport->receive($this->timeout);

            if ($result === null) {
                continue;
            }

            [$response, $responseIp, $responsePort] = $result;

            // 检查响应是否来自预期的服务器
            // 使用解析后的IP地址进行比较，或者如果响应IP在解析结果中也接受
            if (!filter_var($destIp, FILTER_VALIDATE_IP)) {
                // 如果原始目标是域名，尝试获取所有可能的IP
                $allIps = gethostbynamel($destIp) ?: [];
                if (in_array($responseIp, $allIps, true)) {
                    // 响应来自域名解析的其中一个IP，接受它
                    $this->logDebug("接受来自 $responseIp 的响应，它是 $destIp 域名的解析结果之一");
                } else {
                    $this->logWarning("收到来自未知IP的响应: $responseIp (域名 $destIp 解析结果不包含此IP)");
                    continue;
                }
            } else if (!IpUtils::ipEquals($responseIp, $destIp)) {
                // 如果原始目标就是IP地址，直接比较
                $this->logWarning("收到来自未知IP的响应: $responseIp (预期: $destIp)");
                continue;
            }

            // 验证响应事务ID
            if ($response->getTransactionId() !== $request->getTransactionId()) {
                $this->logWarning("收到事务ID不匹配的响应");
                continue;
            }

            $elapsedMs = (microtime(true) - $startTime) * 1000;
            $this->logDebug("收到响应，耗时: " . round($elapsedMs) . "ms");

            return $response;
        }
    }

    /**
     * 记录调试日志
     *
     * @param string $message 日志消息
     */
    private function logDebug(string $message): void
    {
        if ($this->logger !== null) {
            $this->logger->log(LogLevel::DEBUG, "[NatDetector] $message");
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
