<?php

namespace Tourze\Workerman\RFC3489\Tests\Message\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangeRequest;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * ChangeRequest 测试类
 *
 * @internal
 */
#[CoversClass(ChangeRequest::class)]
final class ChangeRequestTest extends TestCase
{
    public function testInheritance(): void
    {
        $attribute = new ChangeRequest();

        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }

    public function testAttributeType(): void
    {
        $attribute = new ChangeRequest();

        $this->assertSame(AttributeType::CHANGE_REQUEST->value, $attribute->getType());
    }

    public function testDefaultValues(): void
    {
        $attribute = new ChangeRequest();

        $this->assertFalse($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
    }

    public function testConstructorWithValues(): void
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

    public function testSetters(): void
    {
        $attribute = new ChangeRequest();

        $attribute->setChangeIp(true);
        $this->assertTrue($attribute->isChangeIp());

        $attribute->setChangePort(true);
        $this->assertTrue($attribute->isChangePort());

        $attribute->setChangeIp(false);
        $this->assertFalse($attribute->isChangeIp());

        $attribute->setChangePort(false);
        $this->assertFalse($attribute->isChangePort());
    }

    public function testLength(): void
    {
        $attribute = new ChangeRequest();

        $this->assertSame(4, $attribute->getLength());
    }

    public function testEncodeNoChanges(): void
    {
        $attribute = new ChangeRequest();
        $encoded = $attribute->encode();

        $this->assertSame("\x00\x00\x00\x00", $encoded);
    }

    public function testEncodeChangeIp(): void
    {
        $attribute = new ChangeRequest(true, false);
        $encoded = $attribute->encode();

        $this->assertSame("\x00\x00\x00\x04", $encoded);
    }

    public function testEncodeChangePort(): void
    {
        $attribute = new ChangeRequest(false, true);
        $encoded = $attribute->encode();

        $this->assertSame("\x00\x00\x00\x02", $encoded);
    }

    public function testEncodeChangeBoth(): void
    {
        $attribute = new ChangeRequest(true, true);
        $encoded = $attribute->encode();

        $this->assertSame("\x00\x00\x00\x06", $encoded);
    }

    public function testDecodeNoChanges(): void
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x00";
        $attribute = ChangeRequest::decode($data, 4, 4);

        $this->assertFalse($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
    }

    public function testDecodeChangeIp(): void
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x04";
        $attribute = ChangeRequest::decode($data, 4, 4);

        $this->assertTrue($attribute->isChangeIp());
        $this->assertFalse($attribute->isChangePort());
    }

    public function testDecodeChangePort(): void
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x02";
        $attribute = ChangeRequest::decode($data, 4, 4);

        $this->assertFalse($attribute->isChangeIp());
        $this->assertTrue($attribute->isChangePort());
    }

    public function testDecodeChangeBoth(): void
    {
        $data = "\x00\x01\x00\x04\x00\x00\x00\x06";
        $attribute = ChangeRequest::decode($data, 4, 4);

        $this->assertTrue($attribute->isChangeIp());
        $this->assertTrue($attribute->isChangePort());
    }

    public function testDecodeInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CHANGE-REQUEST属性长度不足');

        $data = "\x00\x00";
        ChangeRequest::decode($data, 0, 2);
    }

    public function testToStringNoChanges(): void
    {
        $attribute = new ChangeRequest();

        $this->assertSame('CHANGE-REQUEST: None', (string) $attribute);
    }

    public function testToStringChangeIp(): void
    {
        $attribute = new ChangeRequest(true, false);

        $this->assertSame('CHANGE-REQUEST: IP', (string) $attribute);
    }

    public function testToStringChangePort(): void
    {
        $attribute = new ChangeRequest(false, true);

        $this->assertSame('CHANGE-REQUEST: Port', (string) $attribute);
    }

    public function testToStringChangeBoth(): void
    {
        $attribute = new ChangeRequest(true, true);

        $this->assertSame('CHANGE-REQUEST: IP, Port', (string) $attribute);
    }
}
