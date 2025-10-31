<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageFactory;

// 创建一个Binding请求
$request = MessageFactory::createBindingRequest();
echo "创建Binding请求:\n";
echo $request . "\n";

// 创建一个对应的成功响应
$response = MessageFactory::createBindingResponse(
    $request->getTransactionId(),
    '192.168.1.100',
    12345
);
echo "创建Binding成功响应:\n";
echo $response . "\n";

// 创建一个错误响应
$errorResponse = MessageFactory::createBindingErrorResponse(
    $request->getTransactionId(),
    ErrorCode::STALE_CREDENTIALS,
    '凭证已过期'
);
echo "创建Binding错误响应:\n";
echo $errorResponse . "\n";

// 使用通用方法创建响应
$genericResponse = MessageFactory::createSuccessResponse($request);
MessageFactory::addServerAddresses(
    $genericResponse,
    '203.0.113.1',
    3478,
    '203.0.113.2',
    3479
);

// 添加映射地址
$mappedAddress = new MappedAddress('192.168.1.200', 54321);
$genericResponse->addAttribute($mappedAddress);

echo "使用通用方法创建的响应:\n";
echo $genericResponse . "\n";

// 创建一个Shared Secret请求
$ssRequest = MessageFactory::createSharedSecretRequest();
echo "创建Shared Secret请求:\n";
echo $ssRequest . "\n";

// 创建对应的成功响应
$ssResponse = MessageFactory::createSharedSecretResponse(
    $ssRequest->getTransactionId(),
    'user123456',
    'pass987654'
);
echo "创建Shared Secret成功响应:\n";
echo $ssResponse . "\n";

// 添加消息完整性
$messageWithIntegrity = MessageFactory::createBindingRequest();
MessageFactory::addMessageIntegrity($messageWithIntegrity, 'password123');
echo "带有消息完整性的请求:\n";
echo $messageWithIntegrity . "\n";

// 创建未知属性错误响应
$unknownAttrResponse = MessageFactory::createUnknownAttributesResponse(
    $request,
    [0x7F01, 0x8001]
);
echo "未知属性错误响应:\n";
echo $unknownAttrResponse . "\n";
