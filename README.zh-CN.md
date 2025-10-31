# Workerman RFC3489 (STUN协议) 实现

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)
[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-rfc3489.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rfc3489)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Build Status](https://github.com/tourze/php-monorepo/workflows/CI/badge.svg)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/tourze/php-monorepo)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-rfc3489.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rfc3489)

高性能的 RFC3489 STUN 协议实现，支持 NAT 穿透和公网地址发现功能。

## 项目描述

这个库提供了使用 Workerman 框架的完整 RFC3489 STUN（Session Traversal Utilities for NAT）协议实现。
它使应用程序能够发现其公网 IP 地址并确定它们所在的 NAT 类型，这对于点对点通信和媒体流应用程序至关重要。

## 特性

- 完整实现 RFC3489 定义的 STUN 协议
- 支持 Binding 请求和 Shared Secret 请求
- 内置 NAT 类型探测
- 高性能、低延迟
- 简洁易用的 API
- 全面的测试覆盖
- 完整的 IPv4 和 IPv6 支持

## 安装

```bash
composer require tourze/workerman-rfc3489
```

## 快速开始

### 创建 STUN 消息

使用 MessageFactory 可以轻松创建各种类型的 STUN 消息：

```php
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\ErrorCode;

// 创建一个 Binding 请求
$request = MessageFactory::createBindingRequest();

// 创建一个 Binding 响应
$response = MessageFactory::createBindingResponse(
    $request->getTransactionId(),
    '192.168.1.100',
    12345
);

// 创建错误响应
$errorResponse = MessageFactory::createBindingErrorResponse(
    $request->getTransactionId(),
    ErrorCode::SERVER_ERROR,
    '服务器内部错误'
);
```

### 设置 STUN 客户端

```php
use Tourze\Workerman\RFC3489\Protocol\StunClient;

// 创建 STUN 客户端，指定服务器地址和端口
$client = new StunClient('stun.l.google.com', 19302);

// 发现公网 IP 地址
try {
    [$publicIp, $publicPort] = $client->discoverPublicAddress();
    echo "公网 IP: {$publicIp}:{$publicPort}";
} catch (Exception $e) {
    echo "错误: " . $e->getMessage();
} finally {
    $client->close();
}
```

### 设置 STUN 服务器

```php
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerFactory;

// 创建 STUN 服务器工厂
$factory = new StunServerFactory();

// 创建监听端口 3478 的服务器
$server = $factory->createWorkermanServer('0.0.0.0', 3478);

// 启动服务器
$server->start();
```

### NAT 类型检测

```php
use Tourze\Workerman\RFC3489\Protocol\NatTypeDetector;

// 创建 NAT 检测器，指定 STUN 服务器详情
$detector = new NatTypeDetector('stun.l.google.com', 19302);

// 检测 NAT 类型
$natType = $detector->detect();
echo "NAT 类型: " . $natType->value;
echo "描述: " . $natType->getDescription();
echo "支持 P2P: " . ($natType->isSupportP2P() ? '是' : '否');
```

## 使用方法

### 基本配置

```php
use Tourze\Workerman\RFC3489\Protocol\StunClient;
use Psr\Log\LoggerInterface;

// 创建带有自定义超时和日志记录器的客户端
$client = new StunClient(
    'stun.l.google.com', 
    19302,
    null,           // 传输层（null 使用默认 UDP）
    $logger,        // PSR-3 日志记录器实例
    5000           // 超时时间（毫秒）
);
```

### 高级用法

对于更复杂的场景，您可以扩展基类或实现自定义处理程序：

```php
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;
use Tourze\Workerman\RFC3489\Message\StunMessage;

class CustomBindingHandler implements StunMessageHandlerInterface
{
    public function handleMessage(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        // STUN 请求的自定义处理逻辑
        // 返回响应消息，或者如果不需要响应则返回 null
        return $response;
    }
}
```

## 安全性

此实现包含多个安全考虑：

- 事务 ID 验证以防止重放攻击
- 使用 HMAC-SHA1 进行消息完整性检查
- 对格式错误的消息进行适当的错误处理
- 服务器实现的速率限制支持

## 依赖项

- PHP 8.1 或更高版本
- ext-filter
- ext-hash
- ext-sockets
- psr/log
- symfony/yaml
- tourze/enum-extra
- workerman/workerman

## 许可证

此项目采用 MIT 许可证 - 详情请参阅 [LICENSE](LICENSE) 文件。

## 贡献

欢迎贡献！请随时提交 Pull Request。

## 支持

如果您遇到任何问题或有疑问，请在 GitHub 上[创建 issue](https://github.com/tourze/php-monorepo/issues)。
