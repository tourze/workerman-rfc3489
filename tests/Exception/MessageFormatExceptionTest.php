<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * MessageFormatException 测试类
 *
 * @internal
 */
#[CoversClass(MessageFormatException::class)]
final class MessageFormatExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new MessageFormatException();

        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new MessageFormatException();

        $this->assertSame('STUN消息格式错误', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'Custom format error message';
        $exception = new MessageFormatException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCode(): void
    {
        $code = 400;
        $exception = new MessageFormatException('test', $code);

        $this->assertSame($code, $exception->getCode());
    }

    public function testPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new MessageFormatException('test', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
