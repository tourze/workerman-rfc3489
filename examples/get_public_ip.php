<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Protocol\StunClient;
use Tourze\Workerman\RFC3489\Utils\StunLogger;

// 创建一个简单的控制台日志记录器
$logger = new class extends StunLogger {
    public function log($level, $message, array $context = []): void
    {
        $prefix = match ($level) {
            LogLevel::DEBUG => "\033[90m[DEBUG]",
            LogLevel::INFO => "\033[32m[INFO]",
            LogLevel::WARNING => "\033[33m[WARNING]",
            LogLevel::ERROR => "\033[31m[ERROR]",
            LogLevel::CRITICAL => "\033[31;1m[CRITICAL]",
            LogLevel::ALERT => "\033[31;1m[ALERT]",
            LogLevel::EMERGENCY => "\033[31;1m[EMERGENCY]",
            default => "\033[90m[LOG]"
        };

        echo "$prefix $message\033[0m" . PHP_EOL;
    }
};

echo "\033[1m======== STUN 公网IP地址获取工具 ========\033[0m" . PHP_EOL;
echo "基于RFC3489协议实现" . PHP_EOL . PHP_EOL;

// 公共STUN服务器列表
$servers = [
    ['stun.l.google.com', 19302],
    ['stun1.l.google.com', 19302],
    ['stun2.l.google.com', 19302],
    ['stun.schlund.de', 3478],
];

// 选择一个服务器，可以从命令行指定
$serverIndex = 0;
if (isset($argv[1]) && is_numeric($argv[1]) && isset($servers[(int)$argv[1]])) {
    $serverIndex = (int)$argv[1];
} else {
    // 随机选择一个服务器
    $serverIndex = array_rand($servers);
}

$serverAddress = $servers[$serverIndex][0];
$serverPort = $servers[$serverIndex][1];

echo "使用STUN服务器: {$serverAddress}:{$serverPort}" . PHP_EOL;
echo "开始获取公网IP地址，这可能需要几秒钟..." . PHP_EOL . PHP_EOL;

try {
    // 创建STUN客户端
    $client = new StunClient($serverAddress, $serverPort, null, $logger);

    // 开始获取公网地址
    $startTime = microtime(true);
    list($publicIp, $publicPort) = $client->discoverPublicAddress();
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000);

    // 关闭客户端
    $client->close();

    echo "\033[1m======== 获取结果 ========\033[0m" . PHP_EOL;
    echo "公网IP地址: \033[1;33m{$publicIp}\033[0m" . PHP_EOL;
    echo "公网端口: \033[1;33m{$publicPort}\033[0m" . PHP_EOL;
    echo "耗时: {$duration}ms" . PHP_EOL;

    // 获取本机内网地址
    $localAddresses = [];

    // 使用替代方法获取本机内网地址，不依赖socket_getaddrinfo
    $interfaces = net_get_interfaces();
    foreach ($interfaces as $name => $interface) {
        if (isset($interface['unicast']) && is_array($interface['unicast'])) {
            foreach ($interface['unicast'] as $unicast) {
                if (isset($unicast['address']) &&
                    !in_array($unicast['address'], $localAddresses) &&
                    filter_var($unicast['address'], FILTER_VALIDATE_IP) &&
                    !filter_var($unicast['address'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                    $localAddresses[] = $unicast['address'];
                }
            }
        }
    }

    if (!empty($localAddresses)) {
        echo "\033[1m======== 本机地址 ========\033[0m" . PHP_EOL;
        foreach ($localAddresses as $address) {
            echo "内网/本地IP: {$address}" . PHP_EOL;
        }
    }

    // 检查是否存在NAT
    if ($localAddresses && !in_array($publicIp, $localAddresses)) {
        echo "\033[1m您的设备位于NAT后面。\033[0m" . PHP_EOL;
        echo "要检测NAT类型，请运行 nat_type_example.php 脚本。" . PHP_EOL;
    } else {
        echo "\033[1m您的设备可能直接连接到互联网。\033[0m" . PHP_EOL;
    }

} catch (\Throwable $e) {
    echo "\033[31m[错误] 获取公网IP地址时发生异常: " . $e->getMessage() . "\033[0m" . PHP_EOL;

    if (isset($argv[2]) && $argv[2] === '--verbose') {
        echo "\033[31m" . $e->getTraceAsString() . "\033[0m" . PHP_EOL;
    }

    exit(1);
}

echo PHP_EOL . "获取完成！" . PHP_EOL;
