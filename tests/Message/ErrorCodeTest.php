<?php

namespace Tourze\Workerman\RFC3489\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\RFC3489\Message\ErrorCode;

/**
 * @internal
 */
#[CoversClass(ErrorCode::class)]
final class ErrorCodeTest extends AbstractEnumTestCase
{
    public function testGetValue(): void
    {
        $this->assertSame(400, ErrorCode::BAD_REQUEST->value);
        $this->assertSame(401, ErrorCode::UNAUTHORIZED->value);
        $this->assertSame(420, ErrorCode::UNKNOWN_ATTRIBUTE->value);
        $this->assertSame(430, ErrorCode::STALE_CREDENTIALS->value);
        $this->assertSame(431, ErrorCode::INTEGRITY_CHECK_FAILURE->value);
        $this->assertSame(432, ErrorCode::MISSING_USERNAME->value);
        $this->assertSame(433, ErrorCode::USE_TLS->value);
        $this->assertSame(500, ErrorCode::SERVER_ERROR->value);
        $this->assertSame(600, ErrorCode::GLOBAL_FAILURE->value);
    }

    public function testFromValue(): void
    {
        $this->assertSame(ErrorCode::BAD_REQUEST, ErrorCode::fromValue(400));
        $this->assertSame(ErrorCode::UNAUTHORIZED, ErrorCode::fromValue(401));
        $this->assertSame(ErrorCode::UNKNOWN_ATTRIBUTE, ErrorCode::fromValue(420));
        $this->assertSame(ErrorCode::STALE_CREDENTIALS, ErrorCode::fromValue(430));
        $this->assertSame(ErrorCode::INTEGRITY_CHECK_FAILURE, ErrorCode::fromValue(431));
        $this->assertSame(ErrorCode::MISSING_USERNAME, ErrorCode::fromValue(432));
        $this->assertSame(ErrorCode::USE_TLS, ErrorCode::fromValue(433));
        $this->assertSame(ErrorCode::SERVER_ERROR, ErrorCode::fromValue(500));
        $this->assertSame(ErrorCode::GLOBAL_FAILURE, ErrorCode::fromValue(600));
    }

    public function testFromValueWithInvalidValue(): void
    {
        $this->assertNull(ErrorCode::fromValue(999)); // 无效的错误代码
    }

    public function testGetReason(): void
    {
        $this->assertSame('Bad Request', ErrorCode::BAD_REQUEST->getReason());
        $this->assertSame('Unauthorized', ErrorCode::UNAUTHORIZED->getReason());
        $this->assertSame('Unknown Attribute', ErrorCode::UNKNOWN_ATTRIBUTE->getReason());
        $this->assertSame('Stale Credentials', ErrorCode::STALE_CREDENTIALS->getReason());
        $this->assertSame('Integrity Check Failure', ErrorCode::INTEGRITY_CHECK_FAILURE->getReason());
        $this->assertSame('Missing Username', ErrorCode::MISSING_USERNAME->getReason());
        $this->assertSame('Use TLS', ErrorCode::USE_TLS->getReason());
        $this->assertSame('Server Error', ErrorCode::SERVER_ERROR->getReason());
        $this->assertSame('Global Failure', ErrorCode::GLOBAL_FAILURE->getReason());
    }

    public function testGetClass(): void
    {
        // 检查错误类别
        $this->assertSame(4, ErrorCode::BAD_REQUEST->getClass());
        $this->assertSame(4, ErrorCode::UNAUTHORIZED->getClass());
        $this->assertSame(4, ErrorCode::UNKNOWN_ATTRIBUTE->getClass());
        $this->assertSame(4, ErrorCode::STALE_CREDENTIALS->getClass());
        $this->assertSame(4, ErrorCode::INTEGRITY_CHECK_FAILURE->getClass());
        $this->assertSame(4, ErrorCode::MISSING_USERNAME->getClass());
        $this->assertSame(4, ErrorCode::USE_TLS->getClass());
        $this->assertSame(5, ErrorCode::SERVER_ERROR->getClass());
        $this->assertSame(6, ErrorCode::GLOBAL_FAILURE->getClass());
    }

    public function testGetNumber(): void
    {
        // 检查错误编号
        $this->assertSame(0, ErrorCode::BAD_REQUEST->getNumber());
        $this->assertSame(1, ErrorCode::UNAUTHORIZED->getNumber());
        $this->assertSame(20, ErrorCode::UNKNOWN_ATTRIBUTE->getNumber());
        $this->assertSame(30, ErrorCode::STALE_CREDENTIALS->getNumber());
        $this->assertSame(31, ErrorCode::INTEGRITY_CHECK_FAILURE->getNumber());
        $this->assertSame(32, ErrorCode::MISSING_USERNAME->getNumber());
        $this->assertSame(33, ErrorCode::USE_TLS->getNumber());
        $this->assertSame(0, ErrorCode::SERVER_ERROR->getNumber());
        $this->assertSame(0, ErrorCode::GLOBAL_FAILURE->getNumber());
    }

    public function testToString(): void
    {
        $this->assertSame('BAD_REQUEST (400): Bad Request', ErrorCode::BAD_REQUEST->toString());
        $this->assertSame('UNAUTHORIZED (401): Unauthorized', ErrorCode::UNAUTHORIZED->toString());
        $this->assertSame('UNKNOWN_ATTRIBUTE (420): Unknown Attribute', ErrorCode::UNKNOWN_ATTRIBUTE->toString());
        $this->assertSame('STALE_CREDENTIALS (430): Stale Credentials', ErrorCode::STALE_CREDENTIALS->toString());
        $this->assertSame('INTEGRITY_CHECK_FAILURE (431): Integrity Check Failure', ErrorCode::INTEGRITY_CHECK_FAILURE->toString());
        $this->assertSame('MISSING_USERNAME (432): Missing Username', ErrorCode::MISSING_USERNAME->toString());
        $this->assertSame('USE_TLS (433): Use TLS', ErrorCode::USE_TLS->toString());
        $this->assertSame('SERVER_ERROR (500): Server Error', ErrorCode::SERVER_ERROR->toString());
        $this->assertSame('GLOBAL_FAILURE (600): Global Failure', ErrorCode::GLOBAL_FAILURE->toString());
    }

    public function testToArray(): void
    {
        $array = ErrorCode::BAD_REQUEST->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame(400, $array['value']);
        $this->assertSame('Bad Request', $array['label']);

        // 验证数组结构完整性
        $this->assertCount(2, $array);
    }

    #[TestWith([ErrorCode::BAD_REQUEST, 400, 'Bad Request'])]
    #[TestWith([ErrorCode::UNAUTHORIZED, 401, 'Unauthorized'])]
    #[TestWith([ErrorCode::UNKNOWN_ATTRIBUTE, 420, 'Unknown Attribute'])]
    #[TestWith([ErrorCode::STALE_CREDENTIALS, 430, 'Stale Credentials'])]
    #[TestWith([ErrorCode::INTEGRITY_CHECK_FAILURE, 431, 'Integrity Check Failure'])]
    #[TestWith([ErrorCode::MISSING_USERNAME, 432, 'Missing Username'])]
    #[TestWith([ErrorCode::USE_TLS, 433, 'Use TLS'])]
    #[TestWith([ErrorCode::SERVER_ERROR, 500, 'Server Error'])]
    #[TestWith([ErrorCode::GLOBAL_FAILURE, 600, 'Global Failure'])]
    public function testValueAndLabelWithTestWith(ErrorCode $case, int $expectedValue, string $expectedLabel): void
    {
        $this->assertSame($expectedValue, $case->value);
        $this->assertSame($expectedLabel, $case->getLabel());
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ErrorCode::from(9999);
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(ErrorCode::tryFrom(9999));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn ($case) => $case->value, ErrorCode::cases());
        $this->assertSame(count($values), count(array_unique($values)), '所有枚举的 value 必须是唯一的。');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), ErrorCode::cases());
        $this->assertSame(count($labels), count(array_unique($labels)), '所有枚举的 label 必须是唯一的。');
    }
}
