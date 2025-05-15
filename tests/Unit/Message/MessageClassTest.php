<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageClass;

class MessageClassTest extends TestCase
{
    public function testFromMessageTypeWithRequest()
    {
        $messageType = Constants::BINDING_REQUEST;
        $class = MessageClass::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageClass::class, $class);
        $this->assertSame(MessageClass::REQUEST, $class);
    }
    
    public function testFromMessageTypeWithResponse()
    {
        $messageType = Constants::BINDING_RESPONSE;
        $class = MessageClass::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageClass::class, $class);
        $this->assertSame(MessageClass::RESPONSE, $class);
    }
    
    public function testFromMessageTypeWithErrorResponse()
    {
        $messageType = Constants::BINDING_ERROR_RESPONSE;
        $class = MessageClass::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageClass::class, $class);
        $this->assertSame(MessageClass::ERROR_RESPONSE, $class);
    }
    
    public function testFromMessageTypeWithInvalidType()
    {
        $invalidMessageType = 0x999; // 无效的消息类型
        $class = MessageClass::fromMessageType($invalidMessageType);
        
        $this->assertNull($class);
    }
    
    public function testGetValue()
    {
        $this->assertSame(0x0000, MessageClass::REQUEST->value);
        $this->assertSame(0x0100, MessageClass::RESPONSE->value);
        $this->assertSame(0x0110, MessageClass::ERROR_RESPONSE->value);
    }
    
    public function testGetMask()
    {
        $this->assertSame(Constants::CLASS_REQUEST_MASK, MessageClass::REQUEST->value);
        $this->assertSame(Constants::CLASS_SUCCESS_RESPONSE_MASK, MessageClass::RESPONSE->value);
        $this->assertSame(Constants::CLASS_ERROR_RESPONSE_MASK, MessageClass::ERROR_RESPONSE->value);
    }
    
    public function testToString()
    {
        $this->assertSame('REQUEST', MessageClass::REQUEST->toString());
        $this->assertSame('RESPONSE', MessageClass::RESPONSE->toString());
        $this->assertSame('ERROR_RESPONSE', MessageClass::ERROR_RESPONSE->toString());
    }
} 