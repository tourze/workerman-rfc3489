<?php

namespace Tourze\Workerman\RFC3489\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;

/**
 * TimeoutException 测试类
 *
 * @internal
 */
#[CoversClass(TimeoutException::class)]
final class TimeoutExceptionTest extends AbstractExceptionTestCase
{
    public function testInheritance(): void
    {
        $exception = new TimeoutException();

        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testDefaultMessage(): void
    {
        $exception = new TimeoutException();

        $this->assertSame('操作超时', $exception->getMessage());
    }

    public function testCustomMessage(): void
    {
        $message = 'Custom timeout message';
        $exception = new TimeoutException($message);

        $this->assertSame($message, $exception->getMessage());
    }

    public function testTimeout(): void
    {
        $timeout = 5000;
        $exception = new TimeoutException('test', $timeout);

        $this->assertSame($timeout, $exception->getTimeout());
    }

    public function testDefaultTimeout(): void
    {
        $exception = new TimeoutException();

        $this->assertSame(0, $exception->getTimeout());
    }

    public function testReceiveTimeout(): void
    {
        $timeout = 3000;
        $exception = TimeoutException::receiveTimeout($timeout);

        $this->assertSame("接收消息超时（{$timeout}毫秒）", $exception->getMessage());
        $this->assertSame($timeout, $exception->getTimeout());
        $this->assertSame(2001, $exception->getCode());
    }

    public function testSendTimeout(): void
    {
        $timeout = 2000;
        $exception = TimeoutException::sendTimeout($timeout);

        $this->assertSame("发送消息超时（{$timeout}毫秒）", $exception->getMessage());
        $this->assertSame($timeout, $exception->getTimeout());
        $this->assertSame(2002, $exception->getCode());
    }

    public function testTransactionTimeout(): void
    {
        $transactionId = 'test123';
        $timeout = 4000;
        $exception = TimeoutException::transactionTimeout($transactionId, $timeout);

        $hexId = bin2hex($transactionId);
        $this->assertSame("事务 {$hexId} 超时（{$timeout}毫秒）", $exception->getMessage());
        $this->assertSame($timeout, $exception->getTimeout());
        $this->assertSame(2003, $exception->getCode());
    }
}
