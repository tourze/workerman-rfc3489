<?php

namespace Tourze\Workerman\RFC3489\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageClass;

/**
 * @internal
 */
#[CoversClass(MessageClass::class)]
final class MessageClassTest extends AbstractEnumTestCase
{
    public function testFromMessageTypeWithRequest(): void
    {
        $messageType = Constants::BINDING_REQUEST;
        $class = MessageClass::fromMessageType($messageType);

        $this->assertInstanceOf(MessageClass::class, $class);
        $this->assertSame(MessageClass::REQUEST, $class);
    }

    public function testFromMessageTypeWithResponse(): void
    {
        $messageType = Constants::BINDING_RESPONSE;
        $class = MessageClass::fromMessageType($messageType);

        $this->assertInstanceOf(MessageClass::class, $class);
        $this->assertSame(MessageClass::RESPONSE, $class);
    }

    public function testFromMessageTypeWithErrorResponse(): void
    {
        $messageType = Constants::BINDING_ERROR_RESPONSE;
        $class = MessageClass::fromMessageType($messageType);

        $this->assertInstanceOf(MessageClass::class, $class);
        $this->assertSame(MessageClass::ERROR_RESPONSE, $class);
    }

    public function testFromMessageTypeWithInvalidType(): void
    {
        $invalidMessageType = 0x999; // 无效的消息类型
        $class = MessageClass::fromMessageType($invalidMessageType);

        $this->assertNull($class);
    }

    public function testGetValue(): void
    {
        $this->assertSame(0x0000, MessageClass::REQUEST->value);
        $this->assertSame(0x0100, MessageClass::RESPONSE->value);
        $this->assertSame(0x0110, MessageClass::ERROR_RESPONSE->value);
    }

    public function testGetMask(): void
    {
        $this->assertSame(Constants::CLASS_REQUEST_MASK, MessageClass::REQUEST->value);
        $this->assertSame(Constants::CLASS_SUCCESS_RESPONSE_MASK, MessageClass::RESPONSE->value);
        $this->assertSame(Constants::CLASS_ERROR_RESPONSE_MASK, MessageClass::ERROR_RESPONSE->value);
    }

    public function testToString(): void
    {
        $this->assertSame('REQUEST', MessageClass::REQUEST->toString());
        $this->assertSame('RESPONSE', MessageClass::RESPONSE->toString());
        $this->assertSame('ERROR_RESPONSE', MessageClass::ERROR_RESPONSE->toString());
    }

    public function testToArray(): void
    {
        $array = MessageClass::REQUEST->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame(0x0000, $array['value']);
        $this->assertSame('Request', $array['label']);

        // 验证数组结构完整性
        $this->assertCount(2, $array);
    }

    #[TestWith([MessageClass::REQUEST, 0, 'Request'])]
    #[TestWith([MessageClass::RESPONSE, 256, 'Response'])]
    #[TestWith([MessageClass::ERROR_RESPONSE, 272, 'Error Response'])]
    public function testValueAndLabelWithTestWith(MessageClass $case, int $expectedValue, string $expectedLabel): void
    {
        $this->assertSame($expectedValue, $case->value);
        $this->assertSame($expectedLabel, $case->getLabel());
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        MessageClass::from(9999);
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(MessageClass::tryFrom(9999));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn ($case) => $case->value, MessageClass::cases());
        $this->assertSame(count($values), count(array_unique($values)), '所有枚举的 value 必须是唯一的。');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), MessageClass::cases());
        $this->assertSame(count($labels), count(array_unique($labels)), '所有枚举的 label 必须是唯一的。');
    }
}
