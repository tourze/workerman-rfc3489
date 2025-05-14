<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Application\SimpleStunServer;
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

echo "\033[1m======== 简单STUN服务器示例 ========\033[0m" . PHP_EOL;
echo "基于RFC3489协议实现" . PHP_EOL . PHP_EOL;

// 获取命令行参数
$bindIp = isset($argv[1]) ? $argv[1] : '0.0.0.0';
$bindPort = isset($argv[2]) ? (int)$argv[2] : 3478;
$daemon = isset($argv[3]) && strtolower($argv[3]) === 'daemon';

// 备用端口是主端口+1
$alternatePort = $bindPort + 1;

echo "STUN服务器配置：" . PHP_EOL;
echo "监听IP: \033[1;33m$bindIp\033[0m" . PHP_EOL;
echo "监听端口: \033[1;33m$bindPort\033[0m" . PHP_EOL;
echo "备用端口: \033[1;33m$alternatePort\033[0m" . PHP_EOL;
echo "运行模式: " . ($daemon ? "\033[1;33m守护进程\033[0m" : "\033[1;33m前台\033[0m") . PHP_EOL . PHP_EOL;

// 创建配置
$config = new StunConfig([
    'server' => [
        'bind_ip' => $bindIp,
        'bind_port' => $bindPort,
        'alternate_ip' => $bindIp, // 备用IP和绑定IP相同
        'alternate_port' => $alternatePort,
        'workers' => 1,
        'daemon' => $daemon,
        'auth_enabled' => false,
        // 可以设置允许的IP列表，例如:
        // 'allowed_ips' => ['127.0.0.1', '192.168.1.0/24'],
    ],
    'transport' => [
        'socket_timeout' => 30,
        'socket_buffer_size' => 65535,
    ],
    'log' => [
        'enabled' => true,
        'level' => 'info',
    ],
]);

try {
    // 创建SimpleStunServer实例
    $server = new SimpleStunServer($config, $logger);
    
    echo "正在启动STUN服务器..." . PHP_EOL;
    
    if (!$daemon) {
        echo "服务器已启动，按 Ctrl+C 停止" . PHP_EOL . PHP_EOL;
    }
    
    // 启动服务器
    $server->start($daemon);
    
} catch (\Throwable $e) {
    echo "\033[31m[错误] " . $e->getMessage() . "\033[0m" . PHP_EOL;
    
    if (isset($argv[4]) && $argv[4] === '--verbose') {
        echo "\033[31m" . $e->getTraceAsString() . "\033[0m" . PHP_EOL;
    }
    
    exit(1);
} 