<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Application\SimpleStunClient;
use Tourze\Workerman\RFC3489\Application\StunConfig;
use Tourze\Workerman\RFC3489\Utils\StunLogger;

// 创建一个简单的控制台日志记录器
$logger = new class extends StunLogger {
    public function log($level, $message, array $context = []): void
    {
        $prefix = match($level) {
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

echo "\033[1m======== 简单STUN客户端示例 ========\033[0m" . PHP_EOL;
echo "基于RFC3489协议实现" . PHP_EOL . PHP_EOL;

// 获取命令行参数
$serverAddress = isset($argv[1]) ? $argv[1] : 'stun.l.google.com';
$serverPort = isset($argv[2]) ? (int)$argv[2] : 19302;

echo "STUN服务器配置：" . PHP_EOL;
echo "服务器: \033[1;33m$serverAddress\033[0m" . PHP_EOL;
echo "端口: \033[1;33m$serverPort\033[0m" . PHP_EOL . PHP_EOL;

// 创建配置
$config = new StunConfig([
    'client' => [
        'server_address' => $serverAddress,
        'server_port' => $serverPort,
        'timeout' => 5000,
        'retry_count' => 3,
    ],
    'transport' => [
        'local_ip' => '0.0.0.0',
        'local_port' => 0, // 随机端口
        'socket_timeout' => 30,
        'socket_buffer_size' => 65535,
    ],
    'log' => [
        'enabled' => true,
        'level' => 'info',
    ],
]);

try {
    // 创建SimpleStunClient实例
    $client = new SimpleStunClient($config, $logger);
    
    echo "正在启动STUN客户端..." . PHP_EOL;
    $client->start();
    
    // 获取公网地址
    echo PHP_EOL . "正在获取公网地址..." . PHP_EOL;
    [$publicIp, $publicPort] = $client->getPublicAddress();
    
    echo "\033[1m===== 公网地址信息 =====\033[0m" . PHP_EOL;
    echo "公网IP: \033[1;36m$publicIp\033[0m" . PHP_EOL;
    echo "公网端口: \033[1;36m$publicPort\033[0m" . PHP_EOL . PHP_EOL;
    
    // 检测NAT类型
    echo "正在检测NAT类型..." . PHP_EOL;
    $natType = $client->detectNatType();
    
    echo "\033[1m===== NAT类型信息 =====\033[0m" . PHP_EOL;
    echo "NAT类型: \033[1;36m" . $natType->value . "\033[0m" . PHP_EOL;
    echo "描述: \033[1;36m" . $natType->getDescription() . "\033[0m" . PHP_EOL;
    echo "P2P通信支持: \033[1;36m" . ($natType->isSupportP2P() ? "是" : "否") . "\033[0m" . PHP_EOL;
    echo "P2P通信建议: \033[1;36m" . $natType->getP2PAdvice() . "\033[0m" . PHP_EOL;
    
    // 停止客户端
    $client->stop();
    echo PHP_EOL . "STUN客户端已停止" . PHP_EOL;
    
} catch (\Throwable $e) {
    echo "\033[31m[错误] " . $e->getMessage() . "\033[0m" . PHP_EOL;
    
    if (isset($argv[3]) && $argv[3] === '--verbose') {
        echo "\033[31m" . $e->getTraceAsString() . "\033[0m" . PHP_EOL;
    }
    
    exit(1);
} 