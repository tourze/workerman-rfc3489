<?php

namespace Tourze\Workerman\RFC3489\Tests\Message\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\MessageIntegrity;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * MessageIntegrity 测试类
 *
 * @internal
 */
#[CoversClass(MessageIntegrity::class)]
final class MessageIntegrityTest extends TestCase
{
    public function testInheritance(): void
    {
        $attribute = new MessageIntegrity();

        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }

    public function testAttributeType(): void
    {
        $attribute = new MessageIntegrity();

        $this->assertSame(AttributeType::MESSAGE_INTEGRITY->value, $attribute->getType());
    }

    public function testConstructorWithoutHmac(): void
    {
        $attribute = new MessageIntegrity();

        $this->assertSame(Constants::MESSAGE_INTEGRITY_LENGTH, strlen($attribute->getHmac()));
        $this->assertSame(str_repeat("\0", Constants::MESSAGE_INTEGRITY_LENGTH), $attribute->getHmac());
    }

    public function testConstructorWithHmac(): void
    {
        $hmac = str_repeat('a', Constants::MESSAGE_INTEGRITY_LENGTH);
        $attribute = new MessageIntegrity($hmac);

        $this->assertSame($hmac, $attribute->getHmac());
    }

    public function testSetHmac(): void
    {
        $attribute = new MessageIntegrity();
        $hmac = str_repeat('b', Constants::MESSAGE_INTEGRITY_LENGTH);

        $attribute->setHmac($hmac);
        $this->assertSame($hmac, $attribute->getHmac());
    }

    public function testCalculateHmac(): void
    {
        $message = 'test message';
        $key = 'secret key';

        $hmac = MessageIntegrity::calculateHmac($message, $key);

        $this->assertSame(Constants::MESSAGE_INTEGRITY_LENGTH, strlen($hmac));

        $expectedHmac = hash_hmac('sha1', $message, $key, true);
        $this->assertSame($expectedHmac, $hmac);
    }

    public function testCalculateAndSetHmac(): void
    {
        $attribute = new MessageIntegrity();
        $message = 'test message';
        $key = 'secret key';

        $result = $attribute->calculateAndSetHmac($message, $key);

        $this->assertSame($attribute, $result);

        $expectedHmac = hash_hmac('sha1', $message, $key, true);
        $this->assertSame($expectedHmac, $attribute->getHmac());
    }

    public function testVerifyValidHmac(): void
    {
        $message = 'test message';
        $key = 'secret key';
        $hmac = hash_hmac('sha1', $message, $key, true);

        $attribute = new MessageIntegrity($hmac);

        $this->assertTrue($attribute->verify($message, $key));
    }

    public function testVerifyInvalidHmac(): void
    {
        $message = 'test message';
        $key = 'secret key';
        $wrongHmac = str_repeat('x', Constants::MESSAGE_INTEGRITY_LENGTH);

        $attribute = new MessageIntegrity($wrongHmac);

        $this->assertFalse($attribute->verify($message, $key));
    }

    public function testVerifyWithDifferentKey(): void
    {
        $message = 'test message';
        $key = 'secret key';
        $wrongKey = 'wrong key';
        $hmac = hash_hmac('sha1', $message, $key, true);

        $attribute = new MessageIntegrity($hmac);

        $this->assertFalse($attribute->verify($message, $wrongKey));
    }

    public function testGetLength(): void
    {
        $attribute = new MessageIntegrity();

        $this->assertSame(Constants::MESSAGE_INTEGRITY_LENGTH, $attribute->getLength());
    }

    public function testEncode(): void
    {
        $hmac = str_repeat('a', Constants::MESSAGE_INTEGRITY_LENGTH);
        $attribute = new MessageIntegrity($hmac);

        $encoded = $attribute->encode();

        $this->assertSame($hmac, $encoded);
    }

    public function testDecode(): void
    {
        $hmac = str_repeat('a', Constants::MESSAGE_INTEGRITY_LENGTH);
        $data = $hmac;

        $decoded = MessageIntegrity::decode($data, 0, Constants::MESSAGE_INTEGRITY_LENGTH);

        $this->assertSame($hmac, $decoded->getHmac());
    }

    public function testDecodeInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('MESSAGE-INTEGRITY属性长度必须是20字节');

        $data = str_repeat('a', 10); // 错误的长度
        MessageIntegrity::decode($data, 0, 10);
    }

    public function testToString(): void
    {
        $hmac = str_repeat('a', Constants::MESSAGE_INTEGRITY_LENGTH);
        $attribute = new MessageIntegrity($hmac);

        $string = (string) $attribute;

        $this->assertStringContainsString('MESSAGE-INTEGRITY:', $string);
        $this->assertStringContainsString(bin2hex($hmac), $string);
    }
}
