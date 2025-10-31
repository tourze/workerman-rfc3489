<?php

namespace Tourze\Workerman\RFC3489\Tests\Message\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

/**
 * ChangedAddress 测试类
 *
 * @internal
 */
#[CoversClass(ChangedAddress::class)]
final class ChangedAddressTest extends TestCase
{
    public function testInheritance(): void
    {
        $attribute = new ChangedAddress('192.168.1.1', 3478);

        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }

    public function testAttributeType(): void
    {
        $attribute = new ChangedAddress('192.168.1.1', 3478);

        $this->assertSame(AttributeType::CHANGED_ADDRESS->value, $attribute->getType());
    }

    public function testConstructorIPv4(): void
    {
        $ip = '192.168.1.1';
        $port = 3478;
        $attribute = new ChangedAddress($ip, $port);

        $this->assertSame($ip, $attribute->getIp());
        $this->assertSame($port, $attribute->getPort());
        $this->assertSame(IpUtils::IPV4, $attribute->getFamily());
    }

    public function testConstructorIPv6(): void
    {
        $ip = '2001:db8::1';
        $port = 3478;
        $attribute = new ChangedAddress($ip, $port);

        $this->assertSame($ip, $attribute->getIp());
        $this->assertSame($port, $attribute->getPort());
        $this->assertSame(IpUtils::IPV6, $attribute->getFamily());
    }

    public function testLengthIPv4(): void
    {
        $attribute = new ChangedAddress('192.168.1.1', 3478);

        $this->assertSame(8, $attribute->getLength());
    }

    public function testLengthIPv6(): void
    {
        $attribute = new ChangedAddress('2001:db8::1', 3478);

        $this->assertSame(20, $attribute->getLength());
    }

    public function testEncode(): void
    {
        $attribute = new ChangedAddress('192.168.1.1', 3478);
        $encoded = $attribute->encode();

        $this->assertNotEmpty($encoded);
    }

    public function testDecode(): void
    {
        $attribute = new ChangedAddress('192.168.1.1', 3478);
        $encoded = $attribute->encode();

        $decoded = ChangedAddress::decode($encoded, 0, strlen($encoded));

        $this->assertSame($attribute->getIp(), $decoded->getIp());
        $this->assertSame($attribute->getPort(), $decoded->getPort());
        $this->assertSame($attribute->getFamily(), $decoded->getFamily());
    }

    public function testDecodeInvalidData(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无法解析CHANGED-ADDRESS属性');

        $data = "\x00\x00\x00\x00";
        ChangedAddress::decode($data, 0, 4);
    }

    public function testToString(): void
    {
        $attribute = new ChangedAddress('192.168.1.1', 3478);

        $this->assertStringContainsString('CHANGED-ADDRESS:', (string) $attribute);
        $this->assertStringContainsString('192.168.1.1', (string) $attribute);
        $this->assertStringContainsString('3478', (string) $attribute);
    }
}
