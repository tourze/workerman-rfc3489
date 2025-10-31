<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;
use Tourze\Workerman\RFC3489\Utils\StunLogger;

// 创建一个简单的控制台日志记录器
$logger = new class extends StunLogger {
    public function log($level, $message, array $context = []): void
    {
        $prefix = match ($level) {
            LogLevel::DEBUG => '[DEBUG]',
            LogLevel::INFO => '[INFO]',
            LogLevel::WARNING => '[WARNING]',
            LogLevel::ERROR => '[ERROR]',
            LogLevel::CRITICAL => '[CRITICAL]',
            LogLevel::ALERT => '[ALERT]',
            LogLevel::EMERGENCY => '[EMERGENCY]',
            default => '[LOG]',
        };

        echo "{$prefix} {$message}" . PHP_EOL;
    }
};

// 创建传输配置
$config = new TransportConfig(
    '0.0.0.0',    // 绑定到所有接口
    0,            // 使用随机端口
    true,         // 阻塞模式
    3000,         // 发送超时 3 秒
    3000,         // 接收超时 3 秒
    3,            // 失败时最多重试 3 次
    500,          // 重试间隔 0.5 秒
    2048          // 缓冲区大小 2048 字节
);

// 创建UDP传输
$transport = new UdpTransport($config, $logger);

echo "传输层初始化完成！\n";

// 获取本地地址
$localAddress = $transport->getLocalAddress();
if (null !== $localAddress) {
    [$ip, $port] = $localAddress;
    echo "本地绑定地址: {$ip}:{$port}\n";
} else {
    echo "尚未绑定到本地地址\n";
}

// 创建Binding请求消息
$request = MessageFactory::createBindingRequest();
echo "创建Binding请求:\n";
echo $request . "\n";

// 公共STUN服务器
$servers = [
    ['stun.l.google.com', 19302],
    ['stun1.l.google.com', 19302],
    ['stun2.l.google.com', 19302],
    ['stun.schlund.de', 3478],
];

// 选择一个STUN服务器
$server = $servers[array_rand($servers)];
$serverIp = $server[0];
$serverPort = $server[1];

echo "使用STUN服务器: {$serverIp}:{$serverPort}\n";

// 尝试解析服务器域名
if (!filter_var($serverIp, FILTER_VALIDATE_IP)) {
    echo "解析STUN服务器域名: {$serverIp}\n";
    $ips = gethostbynamel($serverIp);
    if (false === $ips || empty($ips)) {
        echo "无法解析STUN服务器域名: {$serverIp}\n";
        exit(1);
    }
    $serverIp = $ips[0];
    echo "STUN服务器IP: {$serverIp}\n";
}

try {
    // 发送请求
    echo "发送Binding请求到 {$serverIp}:{$serverPort}...\n";
    $result = $transport->send($request, $serverIp, $serverPort);

    if ($result) {
        echo "请求已发送，等待响应...\n";

        // 接收响应，超时时间5秒
        $response = $transport->receive(5000);

        if (null !== $response) {
            [$message, $ip, $port] = $response;

            echo "收到来自 {$ip}:{$port} 的响应:\n";
            echo $message . "\n";

            // 检查是否是对我们请求的响应
            if ($message->getTransactionId() === $request->getTransactionId()) {
                echo "事务ID匹配，这是对我们请求的响应\n";

                // 提取映射地址
                $mappedAddress = $message->getAttribute(AttributeType::MAPPED_ADDRESS);

                if (null !== $mappedAddress && $mappedAddress instanceof MappedAddress) {
                    $mappedIp = $mappedAddress->getIp();
                    $mappedPort = $mappedAddress->getPort();

                    echo "您的公网地址是: {$mappedIp}:{$mappedPort}\n";
                } else {
                    echo "响应中没有映射地址信息\n";
                }
            } else {
                echo "事务ID不匹配，这不是对我们请求的响应\n";
            }
        } else {
            echo "接收响应超时\n";

            if (null !== $transport->getLastError()) {
                echo '错误: ' . $transport->getLastError() . "\n";
            }
        }
    } else {
        echo "发送请求失败\n";

        if (null !== $transport->getLastError()) {
            echo '错误: ' . $transport->getLastError() . "\n";
        }
    }
} catch (Throwable $e) {
    echo '发生异常: ' . $e->getMessage() . "\n";
} finally {
    // 关闭传输
    $transport->close();
    echo "传输已关闭\n";
}
