<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message\Attributes;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangeRequest;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * ChangeRequest 测试类
 */
class ChangeRequestTest extends TestCase
{
    public function testInheritance()
    {
        $attribute = new ChangeRequest();
        
        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }
    
    public function testAttributeType()
    {
        $attribute = new ChangeRequest();
        
        $this->assertSame(AttributeType::CHANGE_REQUEST->value, $attribute->getType());
    }
    
    public function testDefaultValues()
    {
        $attribute = new ChangeRequest();
        
        $this->assertFalse($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
    }
    
    public function testConstructorWithValues()
    {
        $attribute = new ChangeRequest(true, false);
        
        $this->assertTrue($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
        
        $attribute = new ChangeRequest(false, true);
        
        $this->assertFalse($attribute->isChangeIp());
        $this->assertTrue($attribute->isChangePort());
        
        $attribute = new ChangeRequest(true, true);
        
        $this->assertTrue($attribute->isChangeIp());
        $this->assertTrue($attribute->isChangePort());
    }
    
    public function testSetters()
    {
        $attribute = new ChangeRequest();
        
        $result = $attribute->setChangeIp(true);
        $this->assertSame($attribute, $result);
        $this->assertTrue($attribute->isChangeIp());
        
        $result = $attribute->setChangePort(true);
        $this->assertSame($attribute, $result);
        $this->assertTrue($attribute->isChangePort());
        
        $attribute->setChangeIp(false);
        $this->assertFalse($attribute->isChangeIp());
        
        $attribute->setChangePort(false);
        $this->assertFalse($attribute->isChangePort());
    }
    
    public function testLength()
    {
        $attribute = new ChangeRequest();
        
        $this->assertSame(4, $attribute->getLength());
    }
    
    public function testEncodeNoChanges()
    {
        $attribute = new ChangeRequest();
        $encoded = $attribute->encode();
        
        $this->assertSame("\x00\x00\x00\x00", $encoded);
    }
    
    public function testEncodeChangeIp()
    {
        $attribute = new ChangeRequest(true, false);
        $encoded = $attribute->encode();
        
        $this->assertSame("\x00\x00\x00\x04", $encoded);
    }
    
    public function testEncodeChangePort()
    {
        $attribute = new ChangeRequest(false, true);
        $encoded = $attribute->encode();
        
        $this->assertSame("\x00\x00\x00\x02", $encoded);
    }
    
    public function testEncodeChangeBoth()
    {
        $attribute = new ChangeRequest(true, true);
        $encoded = $attribute->encode();
        
        $this->assertSame("\x00\x00\x00\x06", $encoded);
    }
    
    public function testDecodeNoChanges()
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x00";
        $attribute = ChangeRequest::decode($data, 4, 4);
        
        $this->assertFalse($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
    }
    
    public function testDecodeChangeIp()
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x04";
        $attribute = ChangeRequest::decode($data, 4, 4);
        
        $this->assertTrue($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
    }
    
    public function testDecodeChangePort()
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x02";
        $attribute = ChangeRequest::decode($data, 4, 4);
        
        $this->assertFalse($attribute->isChangeIp());
        $this->assertTrue($attribute->isChangePort());
    }
    
    public function testDecodeChangeBoth()
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x06";
        $attribute = ChangeRequest::decode($data, 4, 4);
        
        $this->assertTrue($attribute->isChangeIp());
        $this->assertTrue($attribute->isChangePort());
    }
    
    public function testDecodeInvalidLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CHANGE-REQUEST属性长度不足');
        
        $data = "\x00\x00";
        ChangeRequest::decode($data, 0, 2);
    }
    
    public function testToStringNoChanges()
    {
        $attribute = new ChangeRequest();
        
        $this->assertSame('CHANGE-REQUEST: None', (string) $attribute);
    }
    
    public function testToStringChangeIp()
    {
        $attribute = new ChangeRequest(true, false);
        
        $this->assertSame('CHANGE-REQUEST: IP', (string) $attribute);
    }
    
    public function testToStringChangePort()
    {
        $attribute = new ChangeRequest(false, true);
        
        $this->assertSame('CHANGE-REQUEST: Port', (string) $attribute);
    }
    
    public function testToStringChangeBoth()
    {
        $attribute = new ChangeRequest(true, true);
        
        $this->assertSame('CHANGE-REQUEST: IP, Port', (string) $attribute);
    }
}