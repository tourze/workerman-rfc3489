<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageClass;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Message\StunMessage;

class MessageFactoryTest extends TestCase
{
    public function testCreateBindingRequest()
    {
        $message = MessageFactory::createBindingRequest();
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::BINDING_REQUEST, $message->getMessageType());
        $this->assertSame(MessageMethod::BINDING, $message->getMethod());
        $this->assertSame(MessageClass::REQUEST, $message->getClass());
        $this->assertSame(16, strlen($message->getTransactionId()));
    }
    
    public function testCreateBindingRequestWithTransactionId()
    {
        $transactionId = str_repeat("\xAB", 16);
        $message = MessageFactory::createBindingRequest($transactionId);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::BINDING_REQUEST, $message->getMessageType());
        $this->assertSame($transactionId, $message->getTransactionId());
    }
    
    public function testCreateBindingResponse()
    {
        $transactionId = str_repeat("\xAB", 16);
        $mappedIp = '192.168.1.1';
        $mappedPort = 8080;
        
        $message = MessageFactory::createBindingResponse($transactionId, $mappedIp, $mappedPort);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::BINDING_RESPONSE, $message->getMessageType());
        $this->assertSame(MessageMethod::BINDING, $message->getMethod());
        $this->assertSame(MessageClass::RESPONSE, $message->getClass());
        $this->assertSame($transactionId, $message->getTransactionId());
        
        // 验证MAPPED-ADDRESS属性
        $this->assertTrue($message->hasAttribute(AttributeType::MAPPED_ADDRESS));
        $mappedAddress = $message->getAttribute(AttributeType::MAPPED_ADDRESS);
        $this->assertNotNull($mappedAddress);
        $this->assertSame($mappedIp, $mappedAddress->getIp());
        $this->assertSame($mappedPort, $mappedAddress->getPort());
    }
    
    public function testCreateBindingErrorResponse()
    {
        $transactionId = str_repeat("\xAB", 16);
        $errorCode = ErrorCode::SERVER_ERROR;
        $reason = 'Test error reason';
        
        $message = MessageFactory::createBindingErrorResponse($transactionId, $errorCode, $reason);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::BINDING_ERROR_RESPONSE, $message->getMessageType());
        $this->assertSame(MessageMethod::BINDING, $message->getMethod());
        $this->assertSame(MessageClass::ERROR_RESPONSE, $message->getClass());
        $this->assertSame($transactionId, $message->getTransactionId());
        
        // 验证ERROR-CODE属性
        $this->assertTrue($message->hasAttribute(AttributeType::ERROR_CODE));
        $errorCodeAttr = $message->getAttribute(AttributeType::ERROR_CODE);
        $this->assertNotNull($errorCodeAttr);
        $this->assertSame(ErrorCode::SERVER_ERROR->value, $errorCodeAttr->getCode());
        $this->assertSame($reason, $errorCodeAttr->getReason());
    }
    
    public function testCreateBindingErrorResponseWithDefaultReason()
    {
        $transactionId = str_repeat("\xAB", 16);
        $errorCode = ErrorCode::SERVER_ERROR;
        
        $message = MessageFactory::createBindingErrorResponse($transactionId, $errorCode);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::BINDING_ERROR_RESPONSE, $message->getMessageType());
        
        // 验证ERROR-CODE属性
        $errorCodeAttr = $message->getAttribute(AttributeType::ERROR_CODE);
        $this->assertNotNull($errorCodeAttr);
        $this->assertSame(ErrorCode::SERVER_ERROR->value, $errorCodeAttr->getCode());
        $this->assertSame(ErrorCode::SERVER_ERROR->getReason(), $errorCodeAttr->getReason());
    }
    
    public function testCreateSharedSecretRequest()
    {
        $message = MessageFactory::createSharedSecretRequest();
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::SHARED_SECRET_REQUEST, $message->getMessageType());
        $this->assertSame(MessageMethod::SHARED_SECRET, $message->getMethod());
        $this->assertSame(MessageClass::REQUEST, $message->getClass());
        $this->assertSame(16, strlen($message->getTransactionId()));
    }
    
    public function testCreateSharedSecretRequestWithTransactionId()
    {
        $transactionId = str_repeat("\xAB", 16);
        $message = MessageFactory::createSharedSecretRequest($transactionId);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::SHARED_SECRET_REQUEST, $message->getMessageType());
        $this->assertSame($transactionId, $message->getTransactionId());
    }
    
    public function testCreateSharedSecretResponse()
    {
        $transactionId = str_repeat("\xAB", 16);
        $username = 'testuser';
        $password = 'testpass';
        
        $message = MessageFactory::createSharedSecretResponse($transactionId, $username, $password);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::SHARED_SECRET_RESPONSE, $message->getMessageType());
        $this->assertSame(MessageMethod::SHARED_SECRET, $message->getMethod());
        $this->assertSame(MessageClass::RESPONSE, $message->getClass());
        $this->assertSame($transactionId, $message->getTransactionId());
        
        // 验证USERNAME属性
        $this->assertTrue($message->hasAttribute(AttributeType::USERNAME));
        $usernameAttr = $message->getAttribute(AttributeType::USERNAME);
        $this->assertNotNull($usernameAttr);
        $this->assertSame($username, $usernameAttr->getValue());
        
        // 验证PASSWORD属性
        $this->assertTrue($message->hasAttribute(AttributeType::PASSWORD));
        $passwordAttr = $message->getAttribute(AttributeType::PASSWORD);
        $this->assertNotNull($passwordAttr);
        $this->assertSame($password, $passwordAttr->getValue());
    }
    
    public function testCreateSharedSecretErrorResponse()
    {
        $transactionId = str_repeat("\xAB", 16);
        $errorCode = ErrorCode::UNAUTHORIZED;
        $reason = 'Test error reason';
        
        $message = MessageFactory::createSharedSecretErrorResponse($transactionId, $errorCode, $reason);
        
        $this->assertInstanceOf(StunMessage::class, $message);
        $this->assertSame(Constants::SHARED_SECRET_ERROR_RESPONSE, $message->getMessageType());
        $this->assertSame(MessageMethod::SHARED_SECRET, $message->getMethod());
        $this->assertSame(MessageClass::ERROR_RESPONSE, $message->getClass());
        $this->assertSame($transactionId, $message->getTransactionId());
        
        // 验证ERROR-CODE属性
        $this->assertTrue($message->hasAttribute(AttributeType::ERROR_CODE));
        $errorCodeAttr = $message->getAttribute(AttributeType::ERROR_CODE);
        $this->assertNotNull($errorCodeAttr);
        $this->assertSame(ErrorCode::UNAUTHORIZED->value, $errorCodeAttr->getCode());
        $this->assertSame($reason, $errorCodeAttr->getReason());
    }
    
    public function testCreateSuccessResponse()
    {
        $request = MessageFactory::createBindingRequest();
        $response = MessageFactory::createSuccessResponse($request);
        
        $this->assertInstanceOf(StunMessage::class, $response);
        $this->assertSame(Constants::BINDING_RESPONSE, $response->getMessageType());
        $this->assertSame(MessageMethod::BINDING, $response->getMethod());
        $this->assertSame(MessageClass::RESPONSE, $response->getClass());
        $this->assertSame($request->getTransactionId(), $response->getTransactionId());
    }
    
    public function testCreateErrorResponse()
    {
        $request = MessageFactory::createBindingRequest();
        $errorCode = ErrorCode::BAD_REQUEST;
        $reason = 'Test error reason';
        
        $response = MessageFactory::createErrorResponse($request, $errorCode, $reason);
        
        $this->assertInstanceOf(StunMessage::class, $response);
        $this->assertSame(Constants::BINDING_ERROR_RESPONSE, $response->getMessageType());
        $this->assertSame(MessageMethod::BINDING, $response->getMethod());
        $this->assertSame(MessageClass::ERROR_RESPONSE, $response->getClass());
        $this->assertSame($request->getTransactionId(), $response->getTransactionId());
        
        // 验证ERROR-CODE属性
        $this->assertTrue($response->hasAttribute(AttributeType::ERROR_CODE));
        $errorCodeAttr = $response->getAttribute(AttributeType::ERROR_CODE);
        $this->assertNotNull($errorCodeAttr);
        $this->assertSame(ErrorCode::BAD_REQUEST->value, $errorCodeAttr->getCode());
        $this->assertSame($reason, $errorCodeAttr->getReason());
    }
    
    public function testAddMessageIntegrity()
    {
        $message = MessageFactory::createBindingRequest();
        $key = 'test-integrity-key';
        
        $result = MessageFactory::addMessageIntegrity($message, $key);
        
        // 应该返回相同的消息实例（即链式调用）
        $this->assertSame($message, $result);
        
        // 验证消息完整性属性
        $this->assertTrue($message->hasAttribute(AttributeType::MESSAGE_INTEGRITY));
    }
    
    public function testAddServerAddresses()
    {
        $message = MessageFactory::createBindingResponse(str_repeat("\xAB", 16), '192.168.1.1', 8080);
        $sourceIp = '10.0.0.1';
        $sourcePort = 3478;
        $changedIp = '10.0.0.2';
        $changedPort = 3479;
        
        $result = MessageFactory::addServerAddresses($message, $sourceIp, $sourcePort, $changedIp, $changedPort);
        
        // 应该返回相同的消息实例（即链式调用）
        $this->assertSame($message, $result);
        
        // 验证SOURCE-ADDRESS属性
        $this->assertTrue($message->hasAttribute(AttributeType::SOURCE_ADDRESS));
        $sourceAddress = $message->getAttribute(AttributeType::SOURCE_ADDRESS);
        $this->assertNotNull($sourceAddress);
        $this->assertSame($sourceIp, $sourceAddress->getIp());
        $this->assertSame($sourcePort, $sourceAddress->getPort());
        
        // 验证CHANGED-ADDRESS属性
        $this->assertTrue($message->hasAttribute(AttributeType::CHANGED_ADDRESS));
        $changedAddress = $message->getAttribute(AttributeType::CHANGED_ADDRESS);
        $this->assertNotNull($changedAddress);
        $this->assertSame($changedIp, $changedAddress->getIp());
        $this->assertSame($changedPort, $changedAddress->getPort());
    }
    
    public function testAddReflectedFrom()
    {
        $message = MessageFactory::createBindingResponse(str_repeat("\xAB", 16), '192.168.1.1', 8080);
        $reflectedIp = '203.0.113.1';
        $reflectedPort = 12345;
        
        $result = MessageFactory::addReflectedFrom($message, $reflectedIp, $reflectedPort);
        
        // 应该返回相同的消息实例（即链式调用）
        $this->assertSame($message, $result);
        
        // 验证REFLECTED-FROM属性
        $this->assertTrue($message->hasAttribute(AttributeType::REFLECTED_FROM));
        $reflectedFrom = $message->getAttribute(AttributeType::REFLECTED_FROM);
        $this->assertNotNull($reflectedFrom);
        $this->assertSame($reflectedIp, $reflectedFrom->getIp());
        $this->assertSame($reflectedPort, $reflectedFrom->getPort());
    }
}
