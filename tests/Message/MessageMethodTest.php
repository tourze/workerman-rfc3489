<?php

namespace Tourze\Workerman\RFC3489\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageMethod;

/**
 * @internal
 */
#[CoversClass(MessageMethod::class)]
final class MessageMethodTest extends AbstractEnumTestCase
{
    public function testFromMessageTypeWithBinding(): void
    {
        $messageType = Constants::BINDING_REQUEST;
        $method = MessageMethod::fromMessageType($messageType);

        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::BINDING, $method);
    }

    public function testFromMessageTypeWithSharedSecret(): void
    {
        $messageType = Constants::SHARED_SECRET_REQUEST;
        $method = MessageMethod::fromMessageType($messageType);

        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::SHARED_SECRET, $method);
    }

    public function testFromMessageTypeWithBindingResponse(): void
    {
        $messageType = Constants::BINDING_RESPONSE;
        $method = MessageMethod::fromMessageType($messageType);

        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::BINDING, $method);
    }

    public function testFromMessageTypeWithBindingErrorResponse(): void
    {
        $messageType = Constants::BINDING_ERROR_RESPONSE;
        $method = MessageMethod::fromMessageType($messageType);

        $this->assertInstanceOf(MessageMethod::class, $method);
        $this->assertSame(MessageMethod::BINDING, $method);
    }

    public function testFromMessageTypeWithInvalidType(): void
    {
        $invalidMessageType = 0x999; // 无效的消息类型
        $method = MessageMethod::fromMessageType($invalidMessageType);

        $this->assertNull($method);
    }

    public function testFromMethodValue(): void
    {
        $this->assertSame(MessageMethod::BINDING, MessageMethod::fromMethodValue(0x0001));
        $this->assertSame(MessageMethod::SHARED_SECRET, MessageMethod::fromMethodValue(0x0002));
        $this->assertNull(MessageMethod::fromMethodValue(0x0003)); // 无效的方法值
    }

    public function testGetValue(): void
    {
        $this->assertSame(0x0001, MessageMethod::BINDING->value);
        $this->assertSame(0x0002, MessageMethod::SHARED_SECRET->value);
    }

    public function testGetMask(): void
    {
        $this->assertSame(Constants::METHOD_BINDING_MASK, MessageMethod::BINDING->value);
        $this->assertSame(Constants::METHOD_SHARED_SECRET_MASK, MessageMethod::SHARED_SECRET->value);
    }

    public function testToString(): void
    {
        $this->assertSame('BINDING', MessageMethod::BINDING->toString());
        $this->assertSame('SHARED_SECRET', MessageMethod::SHARED_SECRET->toString());
    }

    public function testToArray(): void
    {
        $array = MessageMethod::BINDING->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame(0x0001, $array['value']);
        $this->assertSame('Binding', $array['label']);

        // 验证数组结构完整性
        $this->assertCount(2, $array);
    }

    #[TestWith([MessageMethod::BINDING, 1, 'Binding'])]
    #[TestWith([MessageMethod::SHARED_SECRET, 2, 'Shared Secret'])]
    public function testValueAndLabelWithTestWith(MessageMethod $case, int $expectedValue, string $expectedLabel): void
    {
        $this->assertSame($expectedValue, $case->value);
        $this->assertSame($expectedLabel, $case->getLabel());
    }

    public function testFromThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        MessageMethod::from(9999);
    }

    public function testTryFromReturnsNullForInvalidValue(): void
    {
        $this->assertNull(MessageMethod::tryFrom(9999));
    }

    public function testValueUniqueness(): void
    {
        $values = array_map(fn ($case) => $case->value, MessageMethod::cases());
        $this->assertSame(count($values), count(array_unique($values)), '所有枚举的 value 必须是唯一的。');
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), MessageMethod::cases());
        $this->assertSame(count($labels), count(array_unique($labels)), '所有枚举的 label 必须是唯一的。');
    }
}
