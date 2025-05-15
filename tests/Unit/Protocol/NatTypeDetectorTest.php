<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunTestExecutor;
use Tourze\Workerman\RFC3489\Protocol\NatType;
use Tourze\Workerman\RFC3489\Protocol\NatTypeDetector;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

class NatTypeDetectorTest extends TestCase
{
    /**
     * 测试开放互联网的NAT类型检测
     */
    public function testDetect_OpenInternet()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345]);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 映射地址与本地地址相同
        $mappedAddress = new MappedAddress('192.168.1.2', 12345);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 设置测试II的结果 - 成功
        $testExecutor->method('performTest2')
            ->willReturn(true);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::OPEN_INTERNET, $result);
    }

    /**
     * 测试对称UDP防火墙的NAT类型检测
     */
    public function testDetect_SymmetricUdpFirewall()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345]);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 映射地址与本地地址相同
        $mappedAddress = new MappedAddress('192.168.1.2', 12345);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::SYMMETRIC_UDP_FIREWALL, $result);
    }
    
    /**
     * 测试完全锥形NAT的类型检测
     */
    public function testDetect_FullConeNat()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345]);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 设置测试II的结果 - 成功
        $testExecutor->method('performTest2')
            ->willReturn(true);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::FULL_CONE, $result);
    }
    
    /**
     * 测试受限锥形NAT的类型检测
     */
    public function testDetect_RestrictedConeNat()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345]);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false);
            
        // 设置测试I(改变IP和端口)的结果 - 映射地址相同
        $mappedAddress2 = new MappedAddress('203.0.113.10', 54321);
        $testExecutor->method('performTest1WithChangeRequest')
            ->willReturn($mappedAddress2);
            
        // 设置测试III的结果 - 成功
        $testExecutor->method('performTest3')
            ->willReturn(true);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::RESTRICTED_CONE, $result);
    }
    
    /**
     * 测试端口受限锥形NAT的类型检测
     */
    public function testDetect_PortRestrictedConeNat()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345]);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false);
            
        // 设置测试I(改变IP和端口)的结果 - 映射地址相同
        $mappedAddress2 = new MappedAddress('203.0.113.10', 54321);
        $testExecutor->method('performTest1WithChangeRequest')
            ->willReturn($mappedAddress2);
            
        // 设置测试III的结果 - 失败
        $testExecutor->method('performTest3')
            ->willReturn(false);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::PORT_RESTRICTED_CONE, $result);
    }
    
    /**
     * 测试对称NAT的类型检测
     */
    public function testDetect_SymmetricNat()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345]);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false);
            
        // 设置测试I(改变IP和端口)的结果 - 映射地址不同
        $mappedAddress2 = new MappedAddress('203.0.113.10', 65432); // 不同的端口
        $testExecutor->method('performTest1WithChangeRequest')
            ->willReturn($mappedAddress2);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::SYMMETRIC, $result);
    }
    
    /**
     * 测试阻塞的NAT类型检测
     */
    public function testDetect_Blocked()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 失败
        $testExecutor->method('performTest1')
            ->willReturn(null);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::BLOCKED, $result);
    }
    
    /**
     * 测试无法获取本地地址的情况
     */
    public function testDetect_NoLocalAddress()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        // 设置本地地址为null
        $transport->method('getLocalAddress')
            ->willReturn(null);
            
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I的结果 - 成功
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress]);
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::SYMMETRIC, $result);
    }
    
    /**
     * 测试超时异常的处理
     */
    public function testDetect_TimeoutException()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I抛出超时异常
        $testExecutor->method('performTest1')
            ->will($this->throwException(new TimeoutException('测试超时')));
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::UNKNOWN, $result);
    }
    
    /**
     * 测试传输异常的处理
     */
    public function testDetect_TransportException()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I抛出传输异常
        $testExecutor->method('performTest1')
            ->will($this->throwException(new TransportException('传输错误')));
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 执行检测
        $result = $detector->detect();
        
        // 验证结果
        $this->assertEquals(NatType::UNKNOWN, $result);
    }
    
    /**
     * 测试其他异常的处理
     */
    public function testDetect_OtherException()
    {
        // 创建模拟对象
        $transport = $this->createMock(StunTransport::class);
        $requestSender = $this->createMock(StunRequestSender::class);
        $testExecutor = $this->createMock(StunTestExecutor::class);
        
        $requestSender->method('getTransport')
            ->willReturn($transport);
            
        // 设置测试I抛出一般异常
        $testExecutor->method('performTest1')
            ->will($this->throwException(new \RuntimeException('一般错误')));
            
        // 创建检测器
        $detector = new NatTypeDetector('stun.example.com');
        
        // 使用反射设置私有属性
        $reflectionClass = new \ReflectionClass(NatTypeDetector::class);
        
        $reflectionProperty = $reflectionClass->getProperty('testExecutor');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $testExecutor);
        
        $reflectionProperty = $reflectionClass->getProperty('requestSender');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($detector, $requestSender);
        
        // 预期异常
        $this->expectException(StunException::class);
        
        // 执行检测
        $detector->detect();
    }
}
