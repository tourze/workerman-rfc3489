<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\StunIpException;

/**
 * @internal
 */
#[CoversClass(StunIpException::class)]
final class StunIpExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new StunIpException();
        $this->assertInstanceOf(StunException::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new StunIpException();
        $this->assertSame('', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'IP address processing error';
        $exception = new StunIpException($message);
        $this->assertSame($message, $exception->getMessage());
    }

    public function testCode(): void
    {
        $code = 500;
        $exception = new StunIpException('', $code);
        $this->assertSame($code, $exception->getCode());
    }

    public function testPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new StunIpException('', 0, $previous);
        $this->assertSame($previous, $exception->getPrevious());
    }
}
