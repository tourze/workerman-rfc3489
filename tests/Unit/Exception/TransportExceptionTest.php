<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\TransportException;

class TransportExceptionTest extends TestCase
{
    public function testCreateBasicException()
    {
        $message = 'Test transport exception';
        $code = 123;
        $previous = new \Exception('Previous exception');
        
        $exception = new TransportException($message, $code, $previous);
        
        $this->assertInstanceOf(TransportException::class, $exception);
        $this->assertInstanceOf(StunException::class, $exception);
        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testConnectionFailed()
    {
        $host = '192.168.1.1';
        $port = 3478;
        $reason = 'Connection refused';
        
        $exception = TransportException::connectionFailed($host, $port, $reason);
        
        $this->assertInstanceOf(TransportException::class, $exception);
        
        $message = $exception->getMessage();
        $this->assertStringContainsString('Connection failed', $message);
        $this->assertStringContainsString($host, $message);
        $this->assertStringContainsString((string)$port, $message);
        $this->assertStringContainsString($reason, $message);
    }

    public function testBindFailed()
    {
        $host = '192.168.1.1';
        $port = 3478;
        $reason = 'Address already in use';
        
        $exception = TransportException::bindFailed($host, $port, $reason);
        
        $this->assertInstanceOf(TransportException::class, $exception);
        
        $message = $exception->getMessage();
        $this->assertStringContainsString('Bind failed', $message);
        $this->assertStringContainsString($host, $message);
        $this->assertStringContainsString((string)$port, $message);
        $this->assertStringContainsString($reason, $message);
    }

    public function testSendFailed()
    {
        $host = '192.168.1.1';
        $port = 3478;
        $reason = 'Network unreachable';
        
        $exception = TransportException::sendFailed($host, $port, $reason);
        
        $this->assertInstanceOf(TransportException::class, $exception);
        
        $message = $exception->getMessage();
        $this->assertStringContainsString('Send failed', $message);
        $this->assertStringContainsString($host, $message);
        $this->assertStringContainsString((string)$port, $message);
        $this->assertStringContainsString($reason, $message);
    }
    
    public function testReceiveFailed()
    {
        $reason = 'Socket closed';
        
        $exception = TransportException::receiveFailed($reason);
        
        $this->assertInstanceOf(TransportException::class, $exception);
        
        $message = $exception->getMessage();
        $this->assertStringContainsString('Receive failed', $message);
        $this->assertStringContainsString($reason, $message);
    }
    
    public function testSocketNotInitialized()
    {
        $exception = TransportException::socketNotInitialized();
        
        $this->assertInstanceOf(TransportException::class, $exception);
        
        $message = $exception->getMessage();
        $this->assertStringContainsString('Socket not initialized', $message);
    }
}
