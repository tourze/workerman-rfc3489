<?php

namespace Tourze\Workerman\RFC3489\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * @internal
 */
#[CoversClass(MessageAttribute::class)]
final class MessageAttributeTest extends TestCase
{
    /**
     * 测试抽象类的方法
     */
    public function testGetSetType(): void
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);

        $this->assertSame(AttributeType::USERNAME->value, $attribute->getType());

        $attribute->setType(AttributeType::PASSWORD->value);
        $this->assertSame(AttributeType::PASSWORD->value, $attribute->getType());
    }

    public function testGetSetLength(): void
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);

        // 初始长度应该是0
        $this->assertSame(0, $attribute->getLength());

        $attribute->setLength(16);
        $this->assertSame(16, $attribute->getLength());
    }

    public function testGetSetValue(): void
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);

        // 初始值应该是null
        $this->assertNull($attribute->getValue());

        $value = 'test-value';
        $attribute->setValue($value);
        $this->assertSame($value, $attribute->getValue());
    }

    public function testPadding(): void
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
            $this->assertSame($expectedPadding, $attribute->getPadding(), "Value '{$value}' should have {$expectedPadding} padding bytes");
        }
    }

    public function testToString(): void
    {
        $attribute = new ConcreteMessageAttribute(AttributeType::USERNAME->value);
        $attribute->setValue('testuser');

        // 不直接比较字符串，而是验证格式是否正确
        $string = (string) $attribute;
        $this->assertStringContainsString('USERNAME', $string);
        $this->assertStringContainsString(sprintf('0x%04X', AttributeType::USERNAME->value), $string);
        $this->assertStringContainsString((string) strlen('testuser'), $string);
        $this->assertStringContainsString(bin2hex('testuser'), $string);
    }
}
