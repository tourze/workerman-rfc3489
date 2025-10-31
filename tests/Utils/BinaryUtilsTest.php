<?php

namespace Tourze\Workerman\RFC3489\Tests\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;

/**
 * @internal
 */
#[CoversClass(BinaryUtils::class)]
final class BinaryUtilsTest extends TestCase
{
    public function testWriteUint16(): void
    {
        $value = 43981; // 0xABCD 十六进制
        $encoded = BinaryUtils::writeUint16($value);

        $this->assertSame(2, strlen($encoded));
        $this->assertSame("\xAB\xCD", $encoded);
    }

    public function testWriteUint16WithZero(): void
    {
        $value = 0;
        $encoded = BinaryUtils::writeUint16($value);

        $this->assertSame(2, strlen($encoded));
        $this->assertSame("\x00\x00", $encoded);
    }

    public function testWriteUint16WithMaxValue(): void
    {
        $value = 65535; // 0xFFFF 十六进制
        $encoded = BinaryUtils::writeUint16($value);

        $this->assertSame(2, strlen($encoded));
        $this->assertSame("\xFF\xFF", $encoded);
    }

    public function testReadUint16(): void
    {
        $binary = "\xAB\xCD";
        $decoded = BinaryUtils::readUint16($binary, 0);

        $this->assertSame(43981, $decoded);
    }

    public function testReadUint16WithOffset(): void
    {
        $binary = "\x00\x00\xAB\xCD";
        $decoded = BinaryUtils::readUint16($binary, 2);

        $this->assertSame(43981, $decoded);
    }

    public function testReadUint16WithZero(): void
    {
        $binary = "\x00\x00";
        $decoded = BinaryUtils::readUint16($binary, 0);

        $this->assertSame(0, $decoded);
    }

    public function testReadUint16WithMaxValue(): void
    {
        $binary = "\xFF\xFF";
        $decoded = BinaryUtils::readUint16($binary, 0);

        $this->assertSame(65535, $decoded);
    }

    public function testWriteUint32(): void
    {
        $value = 2882400175; // 0xABCD1234 十六进制
        $encoded = BinaryUtils::writeUint32($value);

        $this->assertSame(4, strlen($encoded));
        $this->assertSame("\xAB\xCD\x12\x34", $encoded);
    }

    public function testWriteUint32WithZero(): void
    {
        $value = 0;
        $encoded = BinaryUtils::writeUint32($value);

        $this->assertSame(4, strlen($encoded));
        $this->assertSame("\x00\x00\x00\x00", $encoded);
    }

    public function testWriteUint32WithMaxValue(): void
    {
        $value = 4294967295; // 0xFFFFFFFF 十六进制
        $encoded = BinaryUtils::writeUint32($value);

        $this->assertSame(4, strlen($encoded));
        $this->assertSame("\xFF\xFF\xFF\xFF", $encoded);
    }

    public function testReadUint32(): void
    {
        $binary = "\xAB\xCD\x12\x34";
        $decoded = BinaryUtils::readUint32($binary, 0);

        $this->assertSame(2882400180, $decoded);
    }

    public function testReadUint32WithOffset(): void
    {
        $binary = "\x00\x00\xAB\xCD\x12\x34";
        $decoded = BinaryUtils::readUint32($binary, 2);

        $this->assertSame(2882400180, $decoded);
    }

    public function testReadUint32WithZero(): void
    {
        $binary = "\x00\x00\x00\x00";
        $decoded = BinaryUtils::readUint32($binary, 0);

        $this->assertSame(0, $decoded);
    }

    public function testReadUint32WithMaxValue(): void
    {
        $binary = "\xFF\xFF\xFF\xFF";
        $decoded = BinaryUtils::readUint32($binary, 0);

        $this->assertSame(4294967295, $decoded);
    }

    public function testPad(): void
    {
        $data = 'test';
        $padded = BinaryUtils::pad($data, 8);

        $this->assertSame(8, strlen($padded));
        $this->assertSame("test\x00\x00\x00\x00", $padded);
    }

    public function testPadWithCustomChar(): void
    {
        $data = 'test';
        $padded = BinaryUtils::pad($data, 8, 'X');

        $this->assertSame(8, strlen($padded));
        $this->assertSame('testXXXX', $padded);
    }

    public function testPadWithNoNeedToPad(): void
    {
        $data = 'testtest';
        $padded = BinaryUtils::pad($data, 8);

        $this->assertSame(8, strlen($padded));
        $this->assertSame('testtest', $padded);
    }

    public function testGetPaddedLength(): void
    {
        $this->assertSame(8, BinaryUtils::getPaddedLength(8, 4));
        $this->assertSame(8, BinaryUtils::getPaddedLength(5, 4));
        $this->assertSame(12, BinaryUtils::getPaddedLength(9, 4));
        $this->assertSame(4, BinaryUtils::getPaddedLength(3, 4));
    }

    public function testNeedsByteSwap(): void
    {
        // 这个测试取决于系统的字节序，确保方法可以正常调用且返回预期类型
        $result = BinaryUtils::needsByteSwap();
        // 字节序检测应该在大端和小端之间选择一个
        $this->assertContains($result, [true, false]);
    }

    public function testSwapBytes16(): void
    {
        $this->assertSame(0xCDAB, BinaryUtils::swapBytes16(0xABCD));
        $this->assertSame(0x3412, BinaryUtils::swapBytes16(0x1234));
        $this->assertSame(0, BinaryUtils::swapBytes16(0));
        $this->assertSame(0xFFFF, BinaryUtils::swapBytes16(0xFFFF));
    }

    public function testSwapBytes32(): void
    {
        $this->assertSame(0x3412CDAB, BinaryUtils::swapBytes32(0xABCD1234));
        $this->assertSame(0, BinaryUtils::swapBytes32(0));
        $this->assertSame(0xFFFFFFFF, BinaryUtils::swapBytes32(0xFFFFFFFF));
    }

    public function testReadUint8(): void
    {
        $binary = "\xAB\xCD";
        $this->assertSame(0xAB, BinaryUtils::readUint8($binary, 0));
        $this->assertSame(0xCD, BinaryUtils::readUint8($binary, 1));
    }

    public function testWriteUint8(): void
    {
        $this->assertSame("\xAB", BinaryUtils::writeUint8(0xAB));
        $this->assertSame("\x00", BinaryUtils::writeUint8(0));
        $this->assertSame("\xFF", BinaryUtils::writeUint8(0xFF));
    }
}
