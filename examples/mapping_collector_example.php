<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Protocol\AddressMappingCollector;
use Tourze\Workerman\RFC3489\Protocol\StunClient;
use Tourze\Workerman\RFC3489\Transport\TransportConfig;
use Tourze\Workerman\RFC3489\Transport\UdpTransport;
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

echo "\033[1m======== 地址映射收集器示例 ========\033[0m" . PHP_EOL;
echo "基于RFC3489协议实现" . PHP_EOL . PHP_EOL;

// 检查命令行参数
if ($argc < 2) {
    echo "用法: php mapping_collector_example.php <STUN服务器1> [STUN服务器2] [STUN服务器3]" . PHP_EOL;
    echo "示例: php mapping_collector_example.php stun.l.google.com stun1.l.google.com stun2.l.google.com" . PHP_EOL;
    echo "至少需要提供一个STUN服务器地址" . PHP_EOL;
    exit(1);
}

// 收集参数中的STUN服务器
$stunServers = [];
for ($i = 1; $i < $argc && $i <= 5; $i++) {
    $server = $argv[$i];
    
    // 检查服务器格式，支持 "server:port" 或 "server"
    if (strpos($server, ':') !== false) {
        [$host, $port] = explode(':', $server);
        $stunServers[] = [$host, (int)$port];
    } else {
        $stunServers[] = [$server, 3478];
    }
}

echo "使用以下STUN服务器：" . PHP_EOL;
foreach ($stunServers as $i => $server) {
    echo ($i + 1) . ". \033[1;33m{$server[0]}:{$server[1]}\033[0m" . PHP_EOL;
}
echo PHP_EOL;

try {
    // 创建地址映射收集器
    $collector = new AddressMappingCollector($logger);
    
    echo "开始收集地址映射信息，这可能需要几秒钟..." . PHP_EOL . PHP_EOL;
    
    // 对每个STUN服务器进行测试
    foreach ($stunServers as $server) {
        [$host, $port] = $server;
        
        echo "正在连接STUN服务器 \033[1;33m{$host}:{$port}\033[0m..." . PHP_EOL;
        
        // 创建传输配置
        $config = new TransportConfig();
        $transport = new UdpTransport($config, $logger);
        
        // 创建STUN客户端
        $client = new StunClient($host, $port, $transport, $logger);
        
        try {
            // 获取本地地址
            $localAddress = $transport->getLocalAddress();
            if ($localAddress === null) {
                echo "\033[33m[警告] 无法获取本地地址，跳过此服务器\033[0m" . PHP_EOL;
                continue;
            }
            
            [$localIp, $localPort] = $localAddress;
            
            // 获取映射地址
            [$mappedIp, $mappedPort] = $client->discoverPublicAddress();
            
            // 添加到收集器
            $collector->add($localIp, $localPort, $host, $port, $mappedIp, $mappedPort);
            
            echo "映射信息: 本地 \033[1;36m{$localIp}:{$localPort}\033[0m -> 映射 \033[1;36m{$mappedIp}:{$mappedPort}\033[0m" . PHP_EOL;
            
            // 对于相同的服务器，再使用不同的端口测试一次
            // 这有助于检测NAT是否依赖于目标端口
            $transport->close();
            $config = new TransportConfig();
            $transport = new UdpTransport($config, $logger);
            $client2 = new StunClient($host, $port + 1, $transport, $logger);
            
            try {
                // 获取本地地址
                $localAddress = $transport->getLocalAddress();
                if ($localAddress === null) {
                    echo "\033[33m[警告] 无法获取本地地址，跳过备用端口测试\033[0m" . PHP_EOL;
                    continue;
                }
                
                [$localIp, $localPort] = $localAddress;
                
                // 获取映射地址
                [$mappedIp2, $mappedPort2] = $client2->discoverPublicAddress();
                
                // 添加到收集器
                $collector->add($localIp, $localPort, $host, $port + 1, $mappedIp2, $mappedPort2);
                
                echo "备用端口映射信息: 本地 \033[1;36m{$localIp}:{$localPort}\033[0m -> 映射 \033[1;36m{$mappedIp2}:{$mappedPort2}\033[0m" . PHP_EOL;
                
            } catch (\Throwable $e) {
                echo "\033[33m[警告] 连接备用端口失败: " . $e->getMessage() . "\033[0m" . PHP_EOL;
            } finally {
                if (isset($client2)) {
                    $client2->close();
                }
            }
            
        } catch (\Throwable $e) {
            echo "\033[33m[警告] 连接服务器失败: " . $e->getMessage() . "\033[0m" . PHP_EOL;
        } finally {
            $client->close();
        }
        
        echo PHP_EOL;
    }
    
    // 显示收集结果
    echo "\033[1m===== 地址映射分析结果 =====\033[0m" . PHP_EOL;
    
    echo "总共收集到 \033[1;36m" . $collector->count() . "\033[0m 个地址映射" . PHP_EOL;
    
    // 分析结果
    if ($collector->count() >= 2) {
        echo "地址复用: " . ($collector->hasAddressReuse() ? "\033[1;32m是\033[0m" : "\033[1;31m否\033[0m") . PHP_EOL;
        echo "地址不一致: " . ($collector->hasAddressInconsistency() ? "\033[1;32m是\033[0m" : "\033[1;31m否\033[0m") . PHP_EOL;
        echo "依赖目标IP: " . ($collector->isDependentOnDestIp() ? "\033[1;32m是\033[0m" : "\033[1;31m否\033[0m") . PHP_EOL;
        echo "依赖目标端口: " . ($collector->isDependentOnDestPort() ? "\033[1;32m是\033[0m" : "\033[1;31m否\033[0m") . PHP_EOL;
        
        $natType = $collector->inferNatType();
        echo "推断的NAT类型: \033[1;36m" . $natType->value . "\033[0m" . PHP_EOL;
        echo "描述: \033[1;36m" . $natType->getDescription() . "\033[0m" . PHP_EOL;
        echo "P2P通信支持: \033[1;36m" . ($natType->isSupportP2P() ? "是" : "否") . "\033[0m" . PHP_EOL;
        echo "P2P通信建议: \033[1;36m" . $natType->getP2PAdvice() . "\033[0m" . PHP_EOL;
    } else {
        echo "\033[33m[警告] 收集到的映射数量不足以推断NAT类型，至少需要2个映射\033[0m" . PHP_EOL;
    }
    
} catch (\Throwable $e) {
    echo "\033[31m[错误] " . $e->getMessage() . "\033[0m" . PHP_EOL;
    
    if (isset($argv[6]) && $argv[6] === '--verbose') {
        echo "\033[31m" . $e->getTraceAsString() . "\033[0m" . PHP_EOL;
    }
    
    exit(1);
} 