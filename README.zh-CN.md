# Workerman RFC3489 (STUN协议) 实现

这是一个使用 Workerman 框架实现的 RFC3489 STUN 协议库，支持 NAT 穿透和公网地址发现功能。

## 特性

- 完整实现 RFC3489 定义的 STUN 协议
- 支持 Binding 请求和 Shared Secret 请求
- 内置 NAT 类型探测
- 高性能、低延迟
- 简洁易用的 API

## 安装

```bash
composer require workerman/rfc3489
```

## 快速开始

### 创建简单的 STUN 消息

使用 MessageFactory 可以轻松创建各种类型的 STUN 消息：

```php
use Tourze\Workerman\RFC3489\Message\MessageFactory;

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

### 创建客户端

```php
// TODO: 待实现客户端示例
```

### 创建服务器

```php
// TODO: 待实现服务器示例
```

## 文档

详细文档请参考 `docs` 目录。

## 示例

更多示例请参考 `examples` 目录。

## 许可证

MIT

## 配置

待补充

## 参考文档

- [示例链接](https://example.com)
