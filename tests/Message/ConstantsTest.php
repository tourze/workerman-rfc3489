<?php

namespace Tourze\Workerman\RFC3489\Tests\Message;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\Constants;

/**
 * @internal
 */
#[CoversClass(Constants::class)]
final class ConstantsTest extends TestCase
{
    public function testMagicCookie(): void
    {
        $this->assertSame(0x2112A442, Constants::MAGIC_COOKIE);
    }

    public function testMessageHeaderLength(): void
    {
        $this->assertSame(20, Constants::MESSAGE_HEADER_LENGTH);
    }

    public function testAttributeHeaderLength(): void
    {
        $this->assertSame(4, Constants::ATTRIBUTE_HEADER_LENGTH);
    }

    public function testMessageTypeLength(): void
    {
        $this->assertSame(2, Constants::MESSAGE_TYPE_LENGTH);
    }

    public function testMessageLengthLength(): void
    {
        $this->assertSame(2, Constants::MESSAGE_LENGTH_LENGTH);
    }

    public function testTransactionIdLength(): void
    {
        $this->assertSame(16, Constants::TRANSACTION_ID_LENGTH);
    }

    public function testMessageTypeMask(): void
    {
        $this->assertSame(0x0110, Constants::MESSAGE_TYPE_MASK);
    }

    public function testMethodMask(): void
    {
        $this->assertSame(0x3EEF, Constants::METHOD_MASK);
    }

    public function testClassMask(): void
    {
        $this->assertSame(0xC110, Constants::CLASS_MASK);
    }

    public function testClassRequestMask(): void
    {
        $this->assertSame(0x0000, Constants::CLASS_REQUEST_MASK);
    }

    public function testClassSuccessResponseMask(): void
    {
        $this->assertSame(0x0100, Constants::CLASS_SUCCESS_RESPONSE_MASK);
    }

    public function testClassErrorResponseMask(): void
    {
        $this->assertSame(0x0110, Constants::CLASS_ERROR_RESPONSE_MASK);
    }

    public function testMethodBindingMask(): void
    {
        $this->assertSame(0x0001, Constants::METHOD_BINDING_MASK);
    }

    public function testMethodSharedSecretMask(): void
    {
        $this->assertSame(0x0002, Constants::METHOD_SHARED_SECRET_MASK);
    }

    public function testBindingRequest(): void
    {
        $this->assertSame(0x0001, Constants::BINDING_REQUEST);
    }

    public function testBindingResponse(): void
    {
        $this->assertSame(0x0101, Constants::BINDING_RESPONSE);
    }

    public function testBindingErrorResponse(): void
    {
        $this->assertSame(0x0111, Constants::BINDING_ERROR_RESPONSE);
    }

    public function testSharedSecretRequest(): void
    {
        $this->assertSame(0x0002, Constants::SHARED_SECRET_REQUEST);
    }

    public function testSharedSecretResponse(): void
    {
        $this->assertSame(0x0102, Constants::SHARED_SECRET_RESPONSE);
    }

    public function testSharedSecretErrorResponse(): void
    {
        $this->assertSame(0x0112, Constants::SHARED_SECRET_ERROR_RESPONSE);
    }
}
