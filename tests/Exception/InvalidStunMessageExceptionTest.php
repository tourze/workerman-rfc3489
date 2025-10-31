<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidStunMessageException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * @internal
 */
#[CoversClass(InvalidStunMessageException::class)]
final class InvalidStunMessageExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new InvalidStunMessageException();
        $this->assertInstanceOf(StunException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new InvalidStunMessageException();
        $this->assertSame('', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'Invalid STUN message format';
        $exception = new InvalidStunMessageException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCode(): void
    {
        $code = 500;
        $exception = new InvalidStunMessageException('', $code);
        $this->assertSame($code, $exception->getCode());
    }

    public function testPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidStunMessageException('', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
