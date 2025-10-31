<?php

namespace Tourze\Workerman\RFC3489\Tests\Message\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\Username;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;

/**
 * @internal
 */
#[CoversClass(Username::class)]
final class UsernameTest extends TestCase
{
    public function testConstructor(): void
    {
        $value = 'testuser';
        $username = new Username($value);

        $this->assertSame(AttributeType::USERNAME->value, $username->getType());
        $this->assertSame($value, $username->getValue());
        $this->assertSame(strlen($value), $username->getLength());
    }

    public function testGetSetValue(): void
    {
        $username = new Username('initial');

        $this->assertSame('initial', $username->getValue());

        $username->setValue('newvalue');
        $this->assertSame('newvalue', $username->getValue());
        $this->assertSame(strlen('newvalue'), $username->getLength());
    }

    public function testEncode(): void
    {
        $value = 'testuser';
        $username = new Username($value);

        $encoded = $username->encode();

        // 预期编码结果：类型(2字节) + 长度(2字节) + 值 + 填充
        $expectedLength = strlen($value);

        // 验证类型和长度
        $this->assertSame(BinaryUtils::encodeUint16(AttributeType::USERNAME->value), substr($encoded, 0, 2));
        $this->assertSame(BinaryUtils::encodeUint16($expectedLength), substr($encoded, 2, 2));

        // 验证属性值
        $this->assertSame($value, substr($encoded, 4, $expectedLength));

        // 验证总长度（包括可能的填充）
        $padding = (4 - ($expectedLength % 4)) % 4; // 计算所需填充
        $this->assertSame(4 + $expectedLength + $padding, strlen($encoded));
    }

    public function testDecode(): void
    {
        $value = 'testuser';
        $expectedLength = strlen($value);
        $padding = (4 - ($expectedLength % 4)) % 4; // 计算所需填充

        // 创建二进制数据
        $data = BinaryUtils::encodeUint16(AttributeType::USERNAME->value);
        $data .= BinaryUtils::encodeUint16($expectedLength);
        $data .= $value;
        $data .= str_repeat("\x00", $padding); // 添加填充

        // 解码
        $offset = 0;
        $username = Username::decode($data, $offset, 4 + $expectedLength + $padding);

        $this->assertInstanceOf(Username::class, $username);
        $this->assertSame(AttributeType::USERNAME->value, $username->getType());
        $this->assertSame($expectedLength, $username->getLength());
        $this->assertSame($value, $username->getValue());
    }

    public function testDecodeWithInvalidType(): void
    {
        // 创建类型不匹配的二进制数据
        $data = BinaryUtils::encodeUint16(AttributeType::PASSWORD->value); // 使用错误的类型
        $data .= BinaryUtils::encodeUint16(4);
        $data .= 'test';

        // 解码
        $offset = 0;
        $this->expectException(InvalidArgumentException::class);
        Username::decode($data, $offset, 8);
    }

    public function testToString(): void
    {
        $value = 'testuser';
        $username = new Username($value);

        $string = (string) $username;

        // 检查字符串包含关键信息
        $this->assertStringContainsString('USERNAME', $string);
        $this->assertStringContainsString('0x0006', $string); // 十六进制类型值
        $this->assertStringContainsString((string) strlen($value), $string); // 长度
        $this->assertStringContainsString($value, $string); // 明文值
    }
}
