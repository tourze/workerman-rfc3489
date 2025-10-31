<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * InvalidArgumentException 测试类
 *
 * @internal
 */
#[CoversClass(InvalidArgumentException::class)]
final class InvalidArgumentExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new InvalidArgumentException('test message');

        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testMessage(): void
    {
        $message = 'Invalid argument provided';
        $exception = new InvalidArgumentException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testCode(): void
    {
        $code = 12345;
        $exception = new InvalidArgumentException('test', $code);

        $this->assertSame($code, $exception->getCode());
    }

    public function testPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidArgumentException('test', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
