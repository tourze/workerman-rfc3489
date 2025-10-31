<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerFactory;
use Tourze\Workerman\RFC3489\Utils\StunLogger;

// 创建日志记录器
$logger = new StunLogger();
// $logger->setLevel(LogLevel::DEBUG);

// 设置STUN服务器配置
$bindIp = '0.0.0.0';           // 绑定到所有网络接口
$bindPort = Constants::DEFAULT_PORT; // 默认端口3478
$alternateIp = '0.0.0.0';       // 备用IP地址
$alternatePort = $bindPort + 1;  // 备用端口3479

echo "----------------------------------------\n";
echo "STUN服务器示例\n";
echo "----------------------------------------\n";
echo "绑定地址: {$bindIp}:{$bindPort}\n";
echo "备用地址: {$alternateIp}:{$alternatePort}\n";
echo "----------------------------------------\n";

// 创建STUN服务器
$server = StunServerFactory::create(
    $bindIp,
    $bindPort,
    $alternateIp,
    $alternatePort,
    null,
    $logger
);

// 处理信号
if (function_exists('pcntl_signal')) {
    // 注册SIGINT信号处理器（Ctrl+C）
    pcntl_signal(SIGINT, function () use ($server): void {
        echo "\n终止服务器...\n";
        $server->stop();
        exit;
    });
}

try {
    // 启动服务器
    echo "启动STUN服务器...\n";
    echo "按 Ctrl+C 终止\n";
    $server->start();
} catch (Exception $e) {
    echo '启动服务器时发生错误: ' . $e->getMessage() . "\n";
    exit(1);
}
