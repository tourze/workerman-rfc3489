<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Exception\StunException;

/**
 * InvalidArgumentException 测试类
 */
class InvalidArgumentExceptionTest extends TestCase
{
    public function testInheritance()
    {
        $exception = new InvalidArgumentException('test message');
        
        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
    
    public function testMessage()
    {
        $message = 'Invalid argument provided';
        $exception = new InvalidArgumentException($message);
        
        $this->assertSame($message, $exception->getMessage());
    }
    
    public function testCode()
    {
        $code = 12345;
        $exception = new InvalidArgumentException('test', $code);
        
        $this->assertSame($code, $exception->getCode());
    }
    
    public function testPrevious()
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidArgumentException('test', 0, $previous);
        
        $this->assertSame($previous, $exception->getPrevious());
    }
}