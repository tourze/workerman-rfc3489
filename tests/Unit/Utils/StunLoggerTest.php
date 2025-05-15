<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Utils\StunLogger;

class StunLoggerTest extends TestCase
{
    public function testConstructorWithLogger()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $stunLogger = new StunLogger($mockLogger);
        
        $this->assertInstanceOf(StunLogger::class, $stunLogger);
    }
    
    public function testLogWithNullLogger()
    {
        $stunLogger = new StunLogger(null);
        
        // 不带记录器的情况下调用日志方法不应该抛出异常
        $stunLogger->log(LogLevel::INFO, 'Test message');
        $stunLogger->log(LogLevel::ERROR, 'Error message', ['context' => 'test']);
        
        $this->expectNotToPerformAssertions();
    }
    
    public function testLogWithLogger()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        // 设置期望：log方法应该被调用一次，参数与我们提供的相匹配
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(LogLevel::INFO),
                $this->equalTo('Test message'),
                $this->equalTo(['context' => 'test'])
            );
        
        $stunLogger = new StunLogger($mockLogger);
        $stunLogger->log(LogLevel::INFO, 'Test message', ['context' => 'test']);
    }
    
    public function testDebugMethod()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        // 设置期望：debug方法应该被调用一次
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(LogLevel::DEBUG),
                $this->equalTo('Debug message'),
                $this->equalTo(['context' => 'test'])
            );
        
        $stunLogger = new StunLogger($mockLogger);
        $stunLogger->debug('Debug message', ['context' => 'test']);
    }
    
    public function testInfoMethod()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        // 设置期望：info方法应该被调用一次
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(LogLevel::INFO),
                $this->equalTo('Info message'),
                $this->equalTo(['context' => 'test'])
            );
        
        $stunLogger = new StunLogger($mockLogger);
        $stunLogger->info('Info message', ['context' => 'test']);
    }
    
    public function testWarningMethod()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        // 设置期望：warning方法应该被调用一次
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(LogLevel::WARNING),
                $this->equalTo('Warning message'),
                $this->equalTo(['context' => 'test'])
            );
        
        $stunLogger = new StunLogger($mockLogger);
        $stunLogger->warning('Warning message', ['context' => 'test']);
    }
    
    public function testErrorMethod()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        
        // 设置期望：error方法应该被调用一次
        $mockLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->equalTo(LogLevel::ERROR),
                $this->equalTo('Error message'),
                $this->equalTo(['context' => 'test'])
            );
        
        $stunLogger = new StunLogger($mockLogger);
        $stunLogger->error('Error message', ['context' => 'test']);
    }
    
    public function testGetInternalLogger()
    {
        $mockLogger = $this->createMock(LoggerInterface::class);
        $stunLogger = new StunLogger($mockLogger);
        
        $this->assertSame($mockLogger, $stunLogger->getInternalLogger());
    }
    
    public function testSetInternalLogger()
    {
        $mockLogger1 = $this->createMock(LoggerInterface::class);
        $mockLogger2 = $this->createMock(LoggerInterface::class);
        
        $stunLogger = new StunLogger($mockLogger1);
        $this->assertSame($mockLogger1, $stunLogger->getInternalLogger());
        
        $stunLogger->setInternalLogger($mockLogger2);
        $this->assertSame($mockLogger2, $stunLogger->getInternalLogger());
    }
} 