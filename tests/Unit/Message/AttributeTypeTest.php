<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\AttributeType;

class AttributeTypeTest extends TestCase
{
    public function testGetValue()
    {
        $this->assertSame(0x0001, AttributeType::MAPPED_ADDRESS->value);
        $this->assertSame(0x0002, AttributeType::RESPONSE_ADDRESS->value);
        $this->assertSame(0x0003, AttributeType::CHANGE_REQUEST->value);
        $this->assertSame(0x0004, AttributeType::SOURCE_ADDRESS->value);
        $this->assertSame(0x0005, AttributeType::CHANGED_ADDRESS->value);
        $this->assertSame(0x0006, AttributeType::USERNAME->value);
        $this->assertSame(0x0007, AttributeType::PASSWORD->value);
        $this->assertSame(0x0008, AttributeType::MESSAGE_INTEGRITY->value);
        $this->assertSame(0x0009, AttributeType::ERROR_CODE->value);
        $this->assertSame(0x000A, AttributeType::UNKNOWN_ATTRIBUTES->value);
        $this->assertSame(0x000B, AttributeType::REFLECTED_FROM->value);
    }
    
    public function testFromValue()
    {
        $this->assertSame(AttributeType::MAPPED_ADDRESS, AttributeType::fromValue(0x0001));
        $this->assertSame(AttributeType::RESPONSE_ADDRESS, AttributeType::fromValue(0x0002));
        $this->assertSame(AttributeType::CHANGE_REQUEST, AttributeType::fromValue(0x0003));
        $this->assertSame(AttributeType::SOURCE_ADDRESS, AttributeType::fromValue(0x0004));
        $this->assertSame(AttributeType::CHANGED_ADDRESS, AttributeType::fromValue(0x0005));
        $this->assertSame(AttributeType::USERNAME, AttributeType::fromValue(0x0006));
        $this->assertSame(AttributeType::PASSWORD, AttributeType::fromValue(0x0007));
        $this->assertSame(AttributeType::MESSAGE_INTEGRITY, AttributeType::fromValue(0x0008));
        $this->assertSame(AttributeType::ERROR_CODE, AttributeType::fromValue(0x0009));
        $this->assertSame(AttributeType::UNKNOWN_ATTRIBUTES, AttributeType::fromValue(0x000A));
        $this->assertSame(AttributeType::REFLECTED_FROM, AttributeType::fromValue(0x000B));
    }
    
    public function testFromValueWithInvalidValue()
    {
        $this->assertNull(AttributeType::fromValue(0x9999)); // 无效的属性类型值
    }
    
    public function testIsKnownAttribute()
    {
        // 测试已知属性类型
        foreach (AttributeType::cases() as $case) {
            $this->assertTrue(AttributeType::isKnownAttribute($case->value));
        }
        
        // 测试未知属性类型
        $this->assertFalse(AttributeType::isKnownAttribute(0x9999));
    }
    
    public function testAddressAttributes()
    {
        $addressAttributes = AttributeType::addressAttributes();
        
        $this->assertContains(AttributeType::MAPPED_ADDRESS, $addressAttributes);
        $this->assertContains(AttributeType::RESPONSE_ADDRESS, $addressAttributes);
        $this->assertContains(AttributeType::SOURCE_ADDRESS, $addressAttributes);
        $this->assertContains(AttributeType::CHANGED_ADDRESS, $addressAttributes);
        $this->assertContains(AttributeType::REFLECTED_FROM, $addressAttributes);
        
        // 非地址属性类型不应该包含在结果中
        $this->assertNotContains(AttributeType::USERNAME, $addressAttributes);
        $this->assertNotContains(AttributeType::PASSWORD, $addressAttributes);
        $this->assertNotContains(AttributeType::MESSAGE_INTEGRITY, $addressAttributes);
        $this->assertNotContains(AttributeType::ERROR_CODE, $addressAttributes);
        $this->assertNotContains(AttributeType::UNKNOWN_ATTRIBUTES, $addressAttributes);
        $this->assertNotContains(AttributeType::CHANGE_REQUEST, $addressAttributes);
    }
    
    public function testIsAddressAttribute()
    {
        // 测试地址属性类型
        $this->assertTrue(AttributeType::isAddressAttribute(AttributeType::MAPPED_ADDRESS->value));
        $this->assertTrue(AttributeType::isAddressAttribute(AttributeType::RESPONSE_ADDRESS->value));
        $this->assertTrue(AttributeType::isAddressAttribute(AttributeType::SOURCE_ADDRESS->value));
        $this->assertTrue(AttributeType::isAddressAttribute(AttributeType::CHANGED_ADDRESS->value));
        $this->assertTrue(AttributeType::isAddressAttribute(AttributeType::REFLECTED_FROM->value));
        
        // 测试非地址属性类型
        $this->assertFalse(AttributeType::isAddressAttribute(AttributeType::USERNAME->value));
        $this->assertFalse(AttributeType::isAddressAttribute(AttributeType::PASSWORD->value));
        $this->assertFalse(AttributeType::isAddressAttribute(AttributeType::MESSAGE_INTEGRITY->value));
        $this->assertFalse(AttributeType::isAddressAttribute(AttributeType::ERROR_CODE->value));
        $this->assertFalse(AttributeType::isAddressAttribute(AttributeType::UNKNOWN_ATTRIBUTES->value));
        $this->assertFalse(AttributeType::isAddressAttribute(AttributeType::CHANGE_REQUEST->value));
        
        // 测试无效属性类型
        $this->assertFalse(AttributeType::isAddressAttribute(0x9999));
    }
    
    public function testToString()
    {
        $this->assertSame('MAPPED_ADDRESS', AttributeType::MAPPED_ADDRESS->toString());
        $this->assertSame('RESPONSE_ADDRESS', AttributeType::RESPONSE_ADDRESS->toString());
        $this->assertSame('CHANGE_REQUEST', AttributeType::CHANGE_REQUEST->toString());
        $this->assertSame('SOURCE_ADDRESS', AttributeType::SOURCE_ADDRESS->toString());
        $this->assertSame('CHANGED_ADDRESS', AttributeType::CHANGED_ADDRESS->toString());
        $this->assertSame('USERNAME', AttributeType::USERNAME->toString());
        $this->assertSame('PASSWORD', AttributeType::PASSWORD->toString());
        $this->assertSame('MESSAGE_INTEGRITY', AttributeType::MESSAGE_INTEGRITY->toString());
        $this->assertSame('ERROR_CODE', AttributeType::ERROR_CODE->toString());
        $this->assertSame('UNKNOWN_ATTRIBUTES', AttributeType::UNKNOWN_ATTRIBUTES->toString());
        $this->assertSame('REFLECTED_FROM', AttributeType::REFLECTED_FROM->toString());
    }
} 