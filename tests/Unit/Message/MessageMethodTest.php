<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageMethod;

class MessageMethodTest extends TestCase
{
    public function testFromMessageTypeWithBinding()
    {
        $messageType = Constants::BINDING_REQUEST;
        $method = MessageMethod::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::BINDING, $method);
    }
    
    public function testFromMessageTypeWithSharedSecret()
    {
        $messageType = Constants::SHARED_SECRET_REQUEST;
        $method = MessageMethod::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::SHARED_SECRET, $method);
    }
    
    public function testFromMessageTypeWithBindingResponse()
    {
        $messageType = Constants::BINDING_RESPONSE;
        $method = MessageMethod::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::BINDING, $method);
    }
    
    public function testFromMessageTypeWithBindingErrorResponse()
    {
        $messageType = Constants::BINDING_ERROR_RESPONSE;
        $method = MessageMethod::fromMessageType($messageType);
        
        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::BINDING, $method);
    }
    
    public function testFromMessageTypeWithInvalidType()
    {
        $invalidMessageType = 0x999; // 无效的消息类型
        $method = MessageMethod::fromMessageType($invalidMessageType);
        
        $this->assertNull($method);
    }
    
    public function testFromMethodValue()
    {
        $this->assertSame(MessageMethod::BINDING, MessageMethod::fromMethodValue(0x0001));
        $this->assertSame(MessageMethod::SHARED_SECRET, MessageMethod::fromMethodValue(0x0002));
        $this->assertNull(MessageMethod::fromMethodValue(0x0003)); // 无效的方法值
    }
    
    public function testGetValue()
    {
        $this->assertSame(0x0001, MessageMethod::BINDING->value);
        $this->assertSame(0x0002, MessageMethod::SHARED_SECRET->value);
    }
    
    public function testGetMask()
    {
        $this->assertSame(Constants::METHOD_BINDING_MASK, MessageMethod::BINDING->value);
        $this->assertSame(Constants::METHOD_SHARED_SECRET_MASK, MessageMethod::SHARED_SECRET->value);
    }
    
    public function testToString()
    {
        $this->assertSame('BINDING', MessageMethod::BINDING->toString());
        $this->assertSame('SHARED_SECRET', MessageMethod::SHARED_SECRET->toString());
    }
} 