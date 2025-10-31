<?php

namespace Tourze\Workerman\RFC3489\Tests\Message\Attributes;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\ErrorCodeAttribute;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * ErrorCodeAttribute 测试类
 *
 * @internal
 */
#[CoversClass(ErrorCodeAttribute::class)]
final class ErrorCodeAttributeTest extends TestCase
{
    public function testInheritance(): void
    {
        $attribute = new ErrorCodeAttribute(400);

        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }

    public function testAttributeType(): void
    {
        $attribute = new ErrorCodeAttribute(400);

        $this->assertSame(AttributeType::ERROR_CODE->value, $attribute->getType());
    }

    public function testConstructorWithIntCode(): void
    {
        $code = 400;
        $reason = 'Bad Request';
        $attribute = new ErrorCodeAttribute($code, $reason);

        $this->assertSame($code, $attribute->getCode());
        $this->assertSame($reason, $attribute->getReason());
    }

    public function testConstructorWithErrorCodeEnum(): void
    {
        $errorCode = ErrorCode::BAD_REQUEST;
        $attribute = new ErrorCodeAttribute($errorCode);

        $this->assertSame($errorCode->value, $attribute->getCode());
        $this->assertSame($errorCode->getDefaultReason(), $attribute->getReason());
    }

    public function testConstructorWithCustomReason(): void
    {
        $errorCode = ErrorCode::BAD_REQUEST;
        $customReason = 'Custom reason';
        $attribute = new ErrorCodeAttribute($errorCode, $customReason);

        $this->assertSame($errorCode->value, $attribute->getCode());
        $this->assertSame($customReason, $attribute->getReason());
    }

    public function testConstructorWithUnknownCode(): void
    {
        $code = 999;
        $attribute = new ErrorCodeAttribute($code);

        $this->assertSame($code, $attribute->getCode());
        $this->assertSame('Unknown Error', $attribute->getReason());
    }

    public function testSetCodeWithInt(): void
    {
        $attribute = new ErrorCodeAttribute(400);
        $newCode = 500;
        $newReason = 'Server Error';

        $attribute->setCode($newCode, $newReason);
        $this->assertSame($newCode, $attribute->getCode());
        $this->assertSame($newReason, $attribute->getReason());
    }

    public function testSetCodeWithEnum(): void
    {
        $attribute = new ErrorCodeAttribute(400);
        $errorCode = ErrorCode::UNAUTHORIZED;

        $attribute->setCode($errorCode);
        $this->assertSame($errorCode->value, $attribute->getCode());
        $this->assertSame($errorCode->getDefaultReason(), $attribute->getReason());
    }

    public function testSetReason(): void
    {
        $attribute = new ErrorCodeAttribute(400);
        $newReason = 'New reason';

        $attribute->setReason($newReason);

        $this->assertSame($newReason, $attribute->getReason());
    }

    public function testGetLength(): void
    {
        $reason = 'Bad Request';
        $attribute = new ErrorCodeAttribute(400, $reason);

        $expectedLength = 4 + strlen($reason);
        $this->assertSame($expectedLength, $attribute->getLength());
    }

    public function testEncode(): void
    {
        $attribute = new ErrorCodeAttribute(400, 'Bad Request');
        $encoded = $attribute->encode();

        $this->assertNotEmpty($encoded);
        $this->assertGreaterThanOrEqual(4, strlen($encoded));
    }

    public function testEncodeDecode(): void
    {
        $originalCode = 400;
        $originalReason = 'Bad Request';
        $attribute = new ErrorCodeAttribute($originalCode, $originalReason);

        $this->assertSame($originalCode, $attribute->getCode());
        $this->assertSame($originalReason, $attribute->getReason());
    }

    public function testDecodeInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ERROR-CODE属性长度不足');

        $data = "\x00\x00"; // 长度不足
        ErrorCodeAttribute::decode($data, 0, 2);
    }

    public function testToString(): void
    {
        $code = 400;
        $reason = 'Bad Request';
        $attribute = new ErrorCodeAttribute($code, $reason);

        $expectedString = "ERROR-CODE: {$code} {$reason}";
        $this->assertSame($expectedString, (string) $attribute);
    }
}
