<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;

/**
 * 用于测试的具体 MessageAttribute 实现
 */
class ConcreteMessageAttribute extends MessageAttribute
{
    protected int $type;
    protected mixed $value = null;
    private int $specificLength = 0;
    
    public function __construct(AttributeType|int $type, mixed $value = null)
    {
        parent::__construct($type, $value);
    }
    
    public function setType(int $type): self
    {
        $this->type = $type;
        return $this;
    }
    
    public function setLength(int $length): self
    {
        $this->specificLength = $length;
        return $this;
    }
    
    public function encode(): string
    {
        return $this->encodeAttributeHeader() . $this->getValue() . str_repeat("\x00", $this->getPadding());
    }
    
    public static function decode(string $data, int $offset, int $length): static
    {
        $attribute = new static(0);
        $attribute->setValue(substr($data, $offset, $length));
        return $attribute;
    }
    
    public function getLength(): int
    {
        if ($this->specificLength > 0) {
            return $this->specificLength;
        }
        return strlen((string)$this->getValue());
    }
    
    public function __toString(): string
    {
        $type = AttributeType::tryFrom($this->getType());
        $typeName = $type ? $type->name : 'UNKNOWN';
        
        return sprintf(
            'Attribute: %s (0x%04X), Length: %d, Value: %s',
            $typeName,
            $this->getType(),
            $this->getLength(),
            bin2hex((string)$this->getValue())
        );
    }
    
    public function getPadding(): int
    {
        $length = $this->getLength();
        if ($length % 4 === 0) {
            return 0;
        }
        
        return 4 - ($length % 4);
    }
    
    /**
     * 编码属性头部
     * 
     * @return string 属性头部的二进制数据
     */
    protected function encodeAttributeHeader(): string
    {
        return BinaryUtils::encodeUint16($this->getType()) . BinaryUtils::encodeUint16($this->getLength());
    }
}

class MessageAttributeTest extends TestCase
{
    /**
     * 测试抽象类的方法
     */
    public function testGetSetType()
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);
        
        $this->assertSame(AttributeType::USERNAME->value, $attribute->getType());
        
        $attribute->setType(AttributeType::PASSWORD->value);
        $this->assertSame(AttributeType::PASSWORD->value, $attribute->getType());
    }
    
    public function testGetSetLength()
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);
        
        // 初始长度应该是0
        $this->assertSame(0, $attribute->getLength());
        
        $attribute->setLength(16);
        $this->assertSame(16, $attribute->getLength());
    }
    
    public function testGetSetValue()
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);
        
        // 初始值应该是null
        $this->assertNull($attribute->getValue());
        
        $value = 'test-value';
        $attribute->setValue($value);
        $this->assertSame($value, $attribute->getValue());
    }
    
    public function testPadding()
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);
        
        // 测试不同长度的数据填充
        $tests = [
            ['', 0],           // 空字符串不需要填充
            ['a', 3],          // 长度1需要填充3字节
            ['ab', 2],         // 长度2需要填充2字节
            ['abc', 1],        // 长度3需要填充1字节
            ['abcd', 0],       // 长度4不需要填充
            ['abcde', 3],      // 长度5需要填充3字节
            ['abcdefg', 1],    // 长度7需要填充1字节
            ['abcdefgh', 0],   // 长度8不需要填充
        ];
        
        foreach ($tests as [$value, $expectedPadding]) {
            $attribute->setValue($value);
            $this->assertSame($expectedPadding, $attribute->getPadding(), "Value '$value' should have $expectedPadding padding bytes");
        }
    }
    
    public function testToString()
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);
        $attribute->setValue('testuser');
        
        // 不直接比较字符串，而是验证格式是否正确
        $string = (string)$attribute;
        $this->assertStringContainsString('USERNAME', $string);
        $this->assertStringContainsString(sprintf('0x%04X', AttributeType::USERNAME->value), $string);
        $this->assertStringContainsString((string)strlen('testuser'), $string);
        $this->assertStringContainsString(bin2hex('testuser'), $string);
    }
} 