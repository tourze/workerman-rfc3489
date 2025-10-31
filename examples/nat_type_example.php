<?php

require_once __DIR__ . '/../../../vendor/autoload.php';

use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Protocol\NatTypeDetector;
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
            default => "\033[90m[LOG]",
        };

        echo "{$prefix} {$message}\033[0m" . PHP_EOL;
    }
};

echo "\033[1m======== STUN NAT类型检测工具 ========\033[0m" . PHP_EOL;
echo '基于RFC3489协议实现' . PHP_EOL . PHP_EOL;

// 检查命令行参数
if ($argc < 2) {
    echo '用法: php nat_type_example.php <STUN服务器地址> [STUN服务器端口]' . PHP_EOL;
    echo '示例: php nat_type_example.php stun.l.google.com 19302' . PHP_EOL;
    exit(1);
}

$stunServer = $argv[1];
$stunPort = isset($argv[2]) ? (int) $argv[2] : 3478;

echo 'STUN服务器配置：' . PHP_EOL;
echo "服务器: \033[1;33m{$stunServer}\033[0m" . PHP_EOL;
echo "端口: \033[1;33m{$stunPort}\033[0m" . PHP_EOL . PHP_EOL;

try {
    echo '开始检测NAT类型，这可能需要几秒钟...' . PHP_EOL . PHP_EOL;

    // 创建NAT类型检测器
    $detector = new NatTypeDetector($stunServer, $stunPort, null, 5000, $logger);

    // 执行检测
    $natType = $detector->detect();

    echo PHP_EOL . '检测完成!' . PHP_EOL . PHP_EOL;

    // 输出检测结果
    echo "\033[1m===== 检测结果 =====\033[0m" . PHP_EOL;
    echo "NAT类型: \033[1;36m" . $natType->value . "\033[0m" . PHP_EOL;
    echo "描述: \033[1;36m" . $natType->getDescription() . "\033[0m" . PHP_EOL;
    echo "P2P通信支持: \033[1;36m" . ($natType->isSupportP2P() ? '是' : '否') . "\033[0m" . PHP_EOL;
    echo "P2P通信建议: \033[1;36m" . $natType->getP2PAdvice() . "\033[0m" . PHP_EOL;
} catch (Throwable $e) {
    echo "\033[31m[错误] " . $e->getMessage() . "\033[0m" . PHP_EOL;

    if (isset($argv[3]) && '--verbose' === $argv[3]) {
        echo "\033[31m" . $e->getTraceAsString() . "\033[0m" . PHP_EOL;
    }

    exit(1);
}
