<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\StunBinaryException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * @internal
 */
#[CoversClass(StunBinaryException::class)]
final class StunBinaryExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new StunBinaryException();
        $this->assertInstanceOf(StunException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new StunBinaryException();
        $this->assertSame('', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'Binary data processing error';
        $exception = new StunBinaryException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCode(): void
    {
        $code = 500;
        $exception = new StunBinaryException('', $code);
        $this->assertSame($code, $exception->getCode());
    }

    public function testPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new StunBinaryException('', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
