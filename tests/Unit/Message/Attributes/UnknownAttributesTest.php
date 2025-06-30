<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message\Attributes;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\UnknownAttributes;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * UnknownAttributes 测试类
 */
class UnknownAttributesTest extends TestCase
{
    public function testInheritance()
    {
        $attribute = new UnknownAttributes();
        
        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }
    
    public function testAttributeType()
    {
        $attribute = new UnknownAttributes();
        
        $this->assertSame(AttributeType::UNKNOWN_ATTRIBUTES->value, $attribute->getType());
    }
    
    public function testConstructorEmpty()
    {
        $attribute = new UnknownAttributes();
        
        $this->assertSame([], $attribute->getAttributes());
    }
    
    public function testConstructorWithAttributes()
    {
        $attributes = [0x0001, 0x0002, 0x0003];
        $attribute = new UnknownAttributes($attributes);
        
        $this->assertSame($attributes, $attribute->getAttributes());
    }
    
    public function testAddAttribute()
    {
        $attribute = new UnknownAttributes();
        
        $result = $attribute->addAttribute(0x0001);
        
        $this->assertSame($attribute, $result);
        $this->assertSame([0x0001], $attribute->getAttributes());
        
        $attribute->addAttribute(0x0002);
        $this->assertSame([0x0001, 0x0002], $attribute->getAttributes());
    }
    
    public function testAddDuplicateAttribute()
    {
        $attribute = new UnknownAttributes([0x0001]);
        
        $attribute->addAttribute(0x0001);
        
        $this->assertSame([0x0001], $attribute->getAttributes());
    }
    
    public function testGetLengthEmpty()
    {
        $attribute = new UnknownAttributes();
        
        $this->assertSame(0, $attribute->getLength());
    }
    
    public function testGetLengthWithAttributes()
    {
        $attributes = [0x0001, 0x0002, 0x0003];
        $attribute = new UnknownAttributes($attributes);
        
        $this->assertSame(6, $attribute->getLength()); // 3 * 2 bytes
    }
    
    public function testEncode()
    {
        $attributes = [0x0001, 0x0002];
        $attribute = new UnknownAttributes($attributes);
        
        $encoded = $attribute->encode();
        

        $this->assertSame(4, strlen($encoded)); // 2 * 2 bytes
        
        // 检查编码的内容
        $decoded = unpack('n*', $encoded);
        $this->assertSame([1 => 0x0001, 2 => 0x0002], $decoded);
    }
    
    public function testEncodeEmpty()
    {
        $attribute = new UnknownAttributes();
        
        $encoded = $attribute->encode();
        
        $this->assertSame('', $encoded);
    }
    
    public function testDecode()
    {
        $attributes = [0x0001, 0x0002, 0x0003];
        $data = pack('n*', ...$attributes);
        
        $decoded = UnknownAttributes::decode($data, 0, strlen($data));
        
        $this->assertSame($attributes, $decoded->getAttributes());
    }
    
    public function testDecodeEmpty()
    {
        $decoded = UnknownAttributes::decode('', 0, 0);
        
        $this->assertSame([], $decoded->getAttributes());
    }
    
    public function testDecodeInvalidLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('UNKNOWN-ATTRIBUTES属性长度必须是2的倍数');
        
        $data = "\x00\x01\x00"; // 3 bytes, 不是2的倍数
        UnknownAttributes::decode($data, 0, 3);
    }
    
    public function testToStringEmpty()
    {
        $attribute = new UnknownAttributes();
        
        $string = (string) $attribute;
        
        $this->assertSame('UNKNOWN-ATTRIBUTES: ', $string);
    }
    
    public function testToStringWithAttributes()
    {
        $attributes = [0x0001, 0x0002];
        $attribute = new UnknownAttributes($attributes);
        
        $string = (string) $attribute;
        
        $this->assertStringContainsString('UNKNOWN-ATTRIBUTES:', $string);
        $this->assertStringContainsString('0x0001', $string);
        $this->assertStringContainsString('0x0002', $string);
    }
}