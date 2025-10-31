<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunTestExecutor;
use Tourze\Workerman\RFC3489\Protocol\NatType;
use Tourze\Workerman\RFC3489\Protocol\NatTypeDetector;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * @internal
 */
#[CoversClass(NatTypeDetector::class)]
final class NatTypeDetectorTest extends TestCase
{
    /**
     * 测试开放互联网的NAT类型检测
     */
    public function testDetectOpenInternet(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：StunTransport 是 STUN 传输层的核心实现，测试需要模拟其网络功能
         * 2) 合理性：在 NAT 类型检测中模拟传输层避免真实网络操作是必要的
         * 3) 替代方案：可以为传输层定义接口，但会增加系统复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 是发送 STUN 请求的核心组件
         * 2) 合理性：测试 NAT 检测时需要模拟请求发送行为来控制测试流程
         * 3) 替代方案：可以定义请求发送器接口，但当前设计已满足测试需求
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 执行具体的 STUN 测试算法，没有相关接口
         * 2) 合理性：测试 NAT 检测器时需要模拟测试执行器的结果来验证决策逻辑
         * 3) 替代方案：可以为测试执行器定义接口，但当前具体类设计已足够清晰
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345])
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 映射地址与本地地址相同
        $mappedAddress = new MappedAddress('192.168.1.2', 12345);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

        // 设置测试II的结果 - 成功
        $testExecutor->method('performTest2')
            ->willReturn(true)
        ;

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
    public function testDetectSymmetricUdpFirewall(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试对称 UDP 防火墙检测需要模拟传输层的地址获取功能
         * 2) 合理性：模拟网络传输层对于测试 NAT 类型检测算法是必要的
         * 3) 替代方案：可以定义传输层接口，但会增加架构复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 在 NAT 检测中负责发送各种测试请求
         * 2) 合理性：测试防火墙类型检测时需要模拟请求发送行为
         * 3) 替代方案：虽然可以使用接口，但当前Mock方式已能满足测试需求
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 执行 STUN 测试的具体算法实现
         * 2) 合理性：测试防火墙检测需要模拟测试执行器的特定结果
         * 3) 替代方案：可以定义测试执行器接口，但当前设计已足够适用
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345])
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 映射地址与本地地址相同
        $mappedAddress = new MappedAddress('192.168.1.2', 12345);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false)
        ;

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
    public function testDetectFullConeNat(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试完全锥形 NAT 需要模拟传输层的本地地址获取
         * 2) 合理性：模拟网络传输层对于验证 NAT 类型判断逻辑是重要的
         * 3) 替代方案：可以为传输层定义接口，但会使架构更加复杂
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 在 NAT 检测中提供请求发送服务
         * 2) 合理性：测试锥形 NAT 检测时需要模拟网络请求发送行为
         * 3) 替代方案：可以使用请求发送器接口，但当前具体类已足够满足测试
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 包含具体的 STUN 测试实现逻辑
         * 2) 合理性：测试锥形 NAT 检测需要模拟测试执行器的特定返回结果
         * 3) 替代方案：虽然可以定义测试执行器接口，但当前设计已经合理
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345])
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

        // 设置测试II的结果 - 成功
        $testExecutor->method('performTest2')
            ->willReturn(true)
        ;

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
    public function testDetectRestrictedConeNat(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试受限锥形 NAT 需要模拟传输层的地址管理功能
         * 2) 合理性：模拟网络传输层对于验证复杂的 NAT 检测算法是必要的
         * 3) 替代方案：可以定义传输层接口，但会增加设计复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供网络请求发送能力
         * 2) 合理性：测试受限锥形 NAT 需要模拟多种网络请求场景
         * 3) 替代方案：可以使用请求发送器接口，但当前的具体类设计已经清晰
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 实现了复杂的 STUN 测试算法
         * 2) 合理性：测试受限锥形 NAT 需要模拟多个测试步骤的结果
         * 3) 替代方案：可以为测试执行器定义接口，但会增加系统复杂度
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345])
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false)
        ;

        // 设置测试I(改变IP和端口)的结果 - 映射地址相同
        $mappedAddress2 = new MappedAddress('203.0.113.10', 54321);
        $testExecutor->method('performTest1WithChangeRequest')
            ->willReturn($mappedAddress2)
        ;

        // 设置测试III的结果 - 成功
        $testExecutor->method('performTest3')
            ->willReturn(true)
        ;

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
    public function testDetectPortRestrictedConeNat(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试端口受限锥形 NAT 需要模拟传输层的端口管理
         * 2) 合理性：模拟网络传输层对于测试精细的 NAT 类型分类是重要的
         * 3) 替代方案：可以为传输层定义接口，但会使系统设计更加复杂
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供网络请求发送服务
         * 2) 合理性：测试端口受限的 NAT 需要模拟不同端口的请求发送
         * 3) 替代方案：虽然可以定义请求发送器接口，但当前设计已经合理
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 包含端口受限 NAT 检测的具体实现
         * 2) 合理性：测试端口受限锥形 NAT 需要模拟特定的测试执行结果
         * 3) 替代方案：可以为测试执行器定义接口，但会增加代码复杂度
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345])
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false)
        ;

        // 设置测试I(改变IP和端口)的结果 - 映射地址相同
        $mappedAddress2 = new MappedAddress('203.0.113.10', 54321);
        $testExecutor->method('performTest1WithChangeRequest')
            ->willReturn($mappedAddress2)
        ;

        // 设置测试III的结果 - 失败
        $testExecutor->method('performTest3')
            ->willReturn(false)
        ;

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
    public function testDetectSymmetricNat(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试对称 NAT 需要模拟传输层的地址和端口管理
         * 2) 合理性：模拟网络传输层对于测试最复杂的 NAT 类型是必要的
         * 3) 替代方案：可以为传输层定义接口，但会使架构更加复杂
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供网络请求发送能力
         * 2) 合理性：测试对称 NAT 需要模拟多种网络请求和响应场景
         * 3) 替代方案：可以使用请求发送器接口，但当前设计已经清晰合理
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 实现了对称 NAT 检测的复杂算法
         * 2) 合理性：测试对称 NAT 需要模拟测试执行器的特定行为和结果
         * 3) 替代方案：虽然可以定义测试执行器接口，但会增加系统复杂度
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址
        $transport->method('getLocalAddress')
            ->willReturn(['192.168.1.2', 12345])
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 映射地址与本地地址不同
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

        // 设置测试II的结果 - 失败
        $testExecutor->method('performTest2')
            ->willReturn(false)
        ;

        // 设置测试I(改变IP和端口)的结果 - 映射地址不同
        $mappedAddress2 = new MappedAddress('203.0.113.10', 65432); // 不同的端口
        $testExecutor->method('performTest1WithChangeRequest')
            ->willReturn($mappedAddress2)
        ;

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
    public function testDetectBlocked(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试阻塞类型检测需要模拟传输层的无法连接情况
         * 2) 合理性：模拟网络传输层对于测试网络不可达情况是必要的
         * 3) 替代方案：可以定义传输层接口，但会增加不必要的复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 在 NAT 检测中负责发送 STUN 请求
         * 2) 合理性：测试阻塞情况需要模拟请求发送器的无法访问行为
         * 3) 替代方案：可以使用请求发送器接口，但当前具体类设计已足够
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 处理各种 STUN 测试场景包括失败情况
         * 2) 合理性：测试阻塞检测需要模拟测试执行器的失败返回结果
         * 3) 替代方案：可以为测试执行器定义接口，但会使设计更加复杂
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 失败
        $testExecutor->method('performTest1')
            ->willReturn(null)
        ;

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
    public function testDetectNoLocalAddress(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试无本地地址情况需要模拟传输层的地址获取失败
         * 2) 合理性：模拟网络传输层对于测试网络配置异常情况是重要的
         * 3) 替代方案：可以为传输层定义接口，但会增加架构复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供请求发送服务
         * 2) 合理性：测试无本地地址场景需要模拟请求发送器的行为
         * 3) 替代方案：可以使用请求发送器接口，但当前设计已经合理
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 处理各种网络环境下的 STUN 测试
         * 2) 合理性：测试网络配置异常需要模拟测试执行器的特定结果
         * 3) 替代方案：虽然可以为测试执行器定义接口，但会增加系统复杂度
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        // 设置本地地址为null
        $transport->method('getLocalAddress')
            ->willReturn(null)
        ;

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I的结果 - 成功
        $mappedAddress = new MappedAddress('203.0.113.10', 54321);
        $changedAddress = new ChangedAddress('203.0.113.5', 3479);
        $testExecutor->method('performTest1')
            ->willReturn([$mappedAddress, $changedAddress])
        ;

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
    public function testDetectTimeoutException(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具伓类：测试超时异常处理需要模拟传输层的基本功能
         * 2) 合理性：模拟网络传输层对于测试异常处理逻辑是必要的
         * 3) 替代方案：可以为传输层定义接口，但会使设计更加复杂
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供请求发送服务
         * 2) 合理性：测试超时异常需要模拟请求发送器的行为
         * 3) 替代方案：可以使用请求发送器接口，但当前具体类设计已满足需求
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 需要模拟抛出超时异常的行为
         * 2) 合理性：测试异常处理需要模拟测试执行器抛出特定异常
         * 3) 替代方案：可以为测试执行器定义接口，但会增加不必要的复杂度
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I抛出超时异常
        $testExecutor->method('performTest1')
            ->will($this->throwException(new TimeoutException('测试超时')))
        ;

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
    public function testDetectTransportException(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试传输异常处理需要模拟传输层的基本功能
         * 2) 合理性：模拟网络传输层对于测试网络异常处理是重要的
         * 3) 替代方案：可以为传输层定义接口，但会增加系统架构复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供请求发送服务
         * 2) 合理性：测试传输异常需要模拟请求发送器的行为
         * 3) 替代方案：可以使用请求发送器接口，但当前设计已经合理
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 需要模拟抛出传输异常的行为
         * 2) 合理性：测试网络异常处理需要模拟测试执行器抛出特定异常
         * 3) 替代方案：可以为测试执行器定义接口，但会使设计更加复杂
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I抛出传输异常
        $testExecutor->method('performTest1')
            ->will($this->throwException(new TransportException('传输错误')))
        ;

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
    public function testDetectOtherException(): void
    {
        /*
         * 使用具体类 StunTransport 创建 Mock 对象：
         * 1) 必须使用具体类：测试其他异常处理需要模拟传输层的基本功能
         * 2) 合理性：模拟网络传输层对于测试全面的异常处理是必要的
         * 3) 替代方案：可以为传输层定义接口，但会增加设计复杂度
         */
        $transport = $this->createMock(StunTransport::class);

        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 为 NAT 检测提供请求发送服务
         * 2) 合理性：测试其他异常需要模拟请求发送器的行为
         * 3) 替代方案：可以使用请求发送器接口，但当前设计已足够清晰
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunTestExecutor 创建 Mock 对象：
         * 1) 必须使用具体类：StunTestExecutor 需要模拟抛出一般异常的行为
         * 2) 合理性：全面测试异常处理需要模拟测试执行器抛出各种异常
         * 3) 替代方案：虽然可以为测试执行器定义接口，但会使系统过度复杂
         */
        $testExecutor = $this->createMock(StunTestExecutor::class);

        $requestSender->method('getTransport')
            ->willReturn($transport)
        ;

        // 设置测试I抛出一般异常
        $testExecutor->method('performTest1')
            ->will($this->throwException(new \RuntimeException('一般错误')))
        ;

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
        $this->expectException(ProtocolException::class);

        // 执行检测
        $detector->detect();
    }
}
