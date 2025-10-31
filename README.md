# Workerman RFC3489 (STUN Protocol) Implementation

[English](README.md) | [中文](README.zh-CN.md)

[![PHP Version](https://img.shields.io/badge/php-%5E8.1-8892BF.svg)](https://php.net/)
[![Latest Version](https://img.shields.io/packagist/v/tourze/workerman-rfc3489.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rfc3489)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![Build Status](https://github.com/tourze/php-monorepo/workflows/CI/badge.svg)](https://github.com/tourze/php-monorepo/actions)
[![Code Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/tourze/php-monorepo)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/workerman-rfc3489.svg?style=flat-square)](https://packagist.org/packages/tourze/workerman-rfc3489)

A high-performance implementation of RFC3489 STUN protocol, supporting NAT traversal and public IP address discovery.

## Description

This library provides a complete implementation of the RFC3489 STUN (Session Traversal Utilities for NAT) 
protocol using the Workerman framework. It enables applications to discover their public IP addresses and 
determine the type of NAT they are behind, which is essential for peer-to-peer communication and media 
streaming applications.

## Features

- Complete RFC3489 STUN protocol implementation
- Support for Binding requests and Shared Secret requests
- Built-in NAT type detection
- High performance and low latency
- Clean and intuitive API
- Comprehensive test coverage
- Full IPv4 and IPv6 support

## Installation

```bash
composer require tourze/workerman-rfc3489
```

## Quick Start

### Creating STUN Messages

Use the MessageFactory to easily create various types of STUN messages:

```php
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\ErrorCode;

// Create a Binding request
$request = MessageFactory::createBindingRequest();

// Create a Binding response
$response = MessageFactory::createBindingResponse(
    $request->getTransactionId(),
    '192.168.1.100',
    12345
);

// Create an error response
$errorResponse = MessageFactory::createBindingErrorResponse(
    $request->getTransactionId(),
    ErrorCode::SERVER_ERROR,
    'Internal server error'
);
```

### Setting up a STUN Client

```php
use Tourze\Workerman\RFC3489\Protocol\StunClient;

// Create STUN client with server address and port
$client = new StunClient('stun.l.google.com', 19302);

// Discover public IP address
try {
    [$publicIp, $publicPort] = $client->discoverPublicAddress();
    echo "Public IP: {$publicIp}:{$publicPort}";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    $client->close();
}
```

### Setting up a STUN Server

```php
use Tourze\Workerman\RFC3489\Protocol\Server\StunServerFactory;

// Create STUN server factory
$factory = new StunServerFactory();

// Create server listening on port 3478
$server = $factory->createWorkermanServer('0.0.0.0', 3478);

// Start server
$server->start();
```

### NAT Type Detection

```php
use Tourze\Workerman\RFC3489\Protocol\NatTypeDetector;

// Create NAT detector with STUN server details
$detector = new NatTypeDetector('stun.l.google.com', 19302);

// Detect NAT type
$natType = $detector->detect();
echo "NAT Type: " . $natType->value;
echo "Description: " . $natType->getDescription();
echo "Supports P2P: " . ($natType->isSupportP2P() ? 'Yes' : 'No');
```

## Usage

### Basic Configuration

```php
use Tourze\Workerman\RFC3489\Protocol\StunClient;
use Psr\Log\LoggerInterface;

// Create client with custom timeout and logger
$client = new StunClient(
    'stun.l.google.com', 
    19302,
    null,           // transport (null for default UDP)
    $logger,        // PSR-3 logger instance
    5000           // timeout in milliseconds
);
```

### Advanced Usage

For more complex scenarios, you can extend the base classes or implement custom handlers:

```php
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;
use Tourze\Workerman\RFC3489\Message\StunMessage;

class CustomBindingHandler implements StunMessageHandlerInterface
{
    public function handleMessage(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        // Custom handling logic for STUN requests
        // Return response message or null if no response needed
        return $response;
    }
}
```

## Security

This implementation includes several security considerations:

- Transaction ID validation to prevent replay attacks
- Message integrity checks using HMAC-SHA1
- Proper error handling for malformed messages
- Rate limiting support for server implementations

## Dependencies

- PHP 8.1 or higher
- ext-filter
- ext-hash
- ext-sockets
- psr/log
- symfony/yaml
- tourze/enum-extra
- workerman/workerman

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Support

If you encounter any issues or have questions, please 
[create an issue](https://github.com/tourze/php-monorepo/issues) on GitHub.