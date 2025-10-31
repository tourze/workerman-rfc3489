<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\StunTransportException;

/**
 * @internal
 */
#[CoversClass(StunTransportException::class)]
final class StunTransportExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new StunTransportException();
        $this->assertInstanceOf(StunException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new StunTransportException();
        $this->assertSame('', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'Transport operation failed';
        $exception = new StunTransportException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCode(): void
    {
        $code = 500;
        $exception = new StunTransportException('', $code);
        $this->assertSame($code, $exception->getCode());
    }

    public function testPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new StunTransportException('', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
