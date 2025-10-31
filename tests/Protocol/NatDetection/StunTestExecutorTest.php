<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol\NatDetection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunRequestSender;
use Tourze\Workerman\RFC3489\Protocol\NatDetection\StunTestExecutor;

/**
 * StunTestExecutor 测试类
 *
 * @internal
 */
#[CoversClass(StunTestExecutor::class)]
final class StunTestExecutorTest extends TestCase
{
    public function testConstructor(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 是 NAT 检测过程中负责发送请求的核心类，没有相应接口
         * 2) 合理性：测试构造函数时需要模拟依赖对象，避免创建真实的网络发送器
         * 3) 替代方案：可以为 StunRequestSender 定义接口，但会增加抽象层，当前设计已满足需求
         */
        $requestSender = $this->createMock(StunRequestSender::class);
        $executor = new StunTestExecutor($requestSender);

        $this->assertInstanceOf(StunTestExecutor::class, $executor);
    }

    public function testPerformTest1(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：StunRequestSender 是执行 STUN 请求的核心组件，测试需要模拟其发送行为
         * 2) 合理性：在单元测试中模拟请求发送器避免真实网络操作是标准做法
         * 3) 替代方案：可以定义请求发送器接口，但当前具体类设计已足够清晰
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 是 STUN 协议响应消息的数据载体
         * 2) 合理性：测试需要模拟服务器响应消息来验证解析逻辑
         * 3) 替代方案：可以为消息定义接口，但会增加协议层的抽象复杂度
         */
        $response = $this->createMock(StunMessage::class);

        /*
         * 使用具体类 MappedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：MappedAddress 是 STUN 协议中映射地址属性的具体实现
         * 2) 合理性：测试需要模拟协议属性对象来验证地址解析功能
         * 3) 替代方案：可以定义属性接口，但 STUN 协议属性相对固定，当前方案已足够
         */
        $mappedAddress = $this->createMock(MappedAddress::class);

        /*
         * 使用具体类 ChangedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：ChangedAddress 是 STUN 协议中变更地址属性的具体实现
         * 2) 合理性：测试需要模拟协议属性来验证 NAT 检测逻辑
         * 3) 替代方案：协议属性类相对稳定，定义接口的必要性不大
         */
        $changedAddress = $this->createMock(ChangedAddress::class);

        // 模拟成功的响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response)
        ;

        $response->expects($this->exactly(2))
            ->method('getAttribute')
            ->willReturnMap([
                [AttributeType::MAPPED_ADDRESS, $mappedAddress],
                [AttributeType::CHANGED_ADDRESS, $changedAddress],
            ])
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest1('stun.example.com', 3478);

        $this->assertNotNull($result);
        $this->assertCount(2, $result);
        $this->assertSame($mappedAddress, $result[0]);
        $this->assertSame($changedAddress, $result[1]);
    }

    public function testPerformTest1WithNoResponse(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试无响应场景需要模拟请求发送器的超时行为
         * 2) 合理性：模拟网络超时情况是测试网络协议代码的常见需求
         * 3) 替代方案：使用接口抽象可能更好，但当前具体类Mock已满足测试需求
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        // 模拟无响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn(null)
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest1('stun.example.com', 3478);

        $this->assertNull($result);
    }

    public function testPerformTest1WithChangeRequest(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试带变更请求的 STUN 测试需要模拟发送器的特定行为
         * 2) 合理性：模拟请求发送器对于测试不同的 STUN 测试类型是必要的
         * 3) 替代方案：可以使用接口，但具体类Mock在此场景下已足够清晰
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 承载 STUN 协议响应数据
         * 2) 合理性：测试变更请求场景需要模拟协议响应消息
         * 3) 替代方案：协议消息类相对稳定，Mock具体类是合理选择
         */
        $response = $this->createMock(StunMessage::class);

        /*
         * 使用具体类 MappedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：MappedAddress 是 STUN 协议的标准属性类
         * 2) 合理性：测试需要模拟映射地址属性来验证 NAT 检测算法
         * 3) 替代方案：STUN 协议属性相对固定，当前方案已足够
         */
        $mappedAddress = $this->createMock(MappedAddress::class);

        // 模拟成功的响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response)
        ;
        $requestSender->expects($this->any())
            ->method('log')
        ;

        $response->expects($this->once())
            ->method('getAttribute')
            ->with(AttributeType::MAPPED_ADDRESS)
            ->willReturn($mappedAddress)
        ;

        $mappedAddress->expects($this->any())
            ->method('getIp')
            ->willReturn('203.0.113.1')
        ;
        $mappedAddress->expects($this->any())
            ->method('getPort')
            ->willReturn(54321)
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest1WithChangeRequest('stun.example.com', 3478, true, false);

        $this->assertSame($mappedAddress, $result);
    }

    public function testPerformTest1WithChangeRequestNoResponse(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试变更请求无响应场景需要模拟发送器的超时行为
         * 2) 合理性：模拟网络超时对于测试 NAT 检测的边界情况是必要的
         * 3) 替代方案：虽然可以使用接口，但当前Mock方式已能有效验证逻辑
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        // 模拟无响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn(null)
        ;
        $requestSender->expects($this->any())
            ->method('log')
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest1WithChangeRequest('stun.example.com', 3478, false, true);

        $this->assertNull($result);
    }

    public function testPerformTest2(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试 STUN Test II 需要模拟请求发送器的行为
         * 2) 合理性：模拟网络请求发送器是单元测试的标准做法
         * 3) 替代方案：可以定义发送器接口，但当前具体类Mock已满足测试覆盖需求
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 ChangedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：ChangedAddress 是 STUN 协议的标准属性，提供变更服务器地址信息
         * 2) 合理性：Test II 需要使用变更地址进行测试，模拟此属性是必要的
         * 3) 替代方案：协议属性类设计相对稳定，Mock具体类是合理选择
         */
        $changedAddress = $this->createMock(ChangedAddress::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 是 STUN 协议消息的核心数据结构
         * 2) 合理性：测试需要模拟服务器响应消息来验证 Test II 的逻辑
         * 3) 替代方案：可以为协议消息定义接口，但会增加协议层抽象复杂度
         */
        $response = $this->createMock(StunMessage::class);

        /*
         * 使用具体类 MappedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：MappedAddress 包含客户端的映射地址信息
         * 2) 合理性：Test II 需要验证映射地址，模拟此属性对象是必要的
         * 3) 替代方案：STUN 协议属性相对固定，当前Mock方案已足够
         */
        $mappedAddress = $this->createMock(MappedAddress::class);

        $changedAddress->expects($this->once())
            ->method('getIp')
            ->willReturn('8.8.4.4')
        ;
        $changedAddress->expects($this->once())
            ->method('getPort')
            ->willReturn(3479)
        ;

        // 模拟成功的响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response)
        ;
        $requestSender->expects($this->any())
            ->method('log')
        ;

        $response->expects($this->once())
            ->method('getAttribute')
            ->with(AttributeType::MAPPED_ADDRESS)
            ->willReturn($mappedAddress)
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest2($changedAddress, 'stun.example.com');

        $this->assertTrue($result);
    }

    public function testPerformTest2WithInvalidChangedAddress(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试无效变更地址场景需要模拟请求发送器的错误处理
         * 2) 合理性：模拟网络请求组件对于测试边界情况和错误处理是必要的
         * 3) 替代方案：虽然接口抽象可能更好，但当前Mock方式已能验证关键逻辑
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 ChangedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：测试需要模拟无效的变更地址（如 0.0.0.0）来验证错误处理
         * 2) 合理性：模拟协议属性的边界值对于测试健壮性是重要的
         * 3) 替代方案：协议属性类结构相对固定，Mock具体类是适当选择
         */
        $changedAddress = $this->createMock(ChangedAddress::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 承载协议响应数据
         * 2) 合理性：即使在错误场景下也需要模拟响应消息来测试处理逻辑
         * 3) 替代方案：协议消息类设计相对稳定，当前方案已满足需求
         */
        $response = $this->createMock(StunMessage::class);

        /*
         * 使用具体类 MappedAddress 创建 Mock 对象：
         * 1) 必须使用具体类：MappedAddress 提供映射地址信息用于验证
         * 2) 合理性：错误场景下仍需要模拟映射地址来测试完整的处理流程
         * 3) 替代方案：STUN 协议属性相对标准化，Mock具体类是合理选择
         */
        $mappedAddress = $this->createMock(MappedAddress::class);

        // 模拟无效的变更地址IP (0.0.0.0)
        $changedAddress->expects($this->once())
            ->method('getIp')
            ->willReturn('0.0.0.0')
        ;
        $changedAddress->expects($this->once())
            ->method('getPort')
            ->willReturn(3479)
        ;

        // 模拟成功的响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->with(self::anything(), 'stun.example.com', 3479) // 应该使用原始服务器IP
            ->willReturn($response)
        ;
        $requestSender->expects($this->any())
            ->method('log')
        ;

        $response->expects($this->once())
            ->method('getAttribute')
            ->with(AttributeType::MAPPED_ADDRESS)
            ->willReturn($mappedAddress)
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest2($changedAddress, 'stun.example.com');

        $this->assertTrue($result);
    }

    public function testPerformTest3(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试 STUN Test III 需要模拟请求发送器的基本发送功能
         * 2) 合理性：Test III 是 NAT 检测算法的重要组成部分，模拟发送器是必要的
         * 3) 替代方案：可以使用接口抽象，但当前具体类Mock已能充分验证功能
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        /*
         * 使用具体类 StunMessage 创建 Mock 对象：
         * 1) 必须使用具体类：StunMessage 是 STUN 协议响应的数据载体
         * 2) 合理性：Test III 需要验证是否能接收到响应，模拟响应消息是合理的
         * 3) 替代方案：协议消息类相对稳定，Mock具体类是适当的测试方式
         */
        $response = $this->createMock(StunMessage::class);

        // 模拟成功的响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn($response)
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest3('stun.example.com', 3478);

        $this->assertTrue($result);
    }

    public function testPerformTest3WithNoResponse(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试 Test III 无响应场景需要模拟发送器的超时行为
         * 2) 合理性：模拟网络超时情况对于测试 NAT 检测的完整性是重要的
         * 3) 替代方案：虽然接口抽象可能更优雅，但当前Mock方式已能有效测试边界情况
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        // 模拟无响应
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willReturn(null)
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest3('stun.example.com', 3478);

        $this->assertFalse($result);
    }

    public function testPerformTest3WithException(): void
    {
        /*
         * 使用具体类 StunRequestSender 创建 Mock 对象：
         * 1) 必须使用具体类：测试异常处理需要模拟发送器抛出特定异常
         * 2) 合理性：模拟网络传输异常对于测试错误处理逻辑的健壮性是必要的
         * 3) 替代方案：可以使用接口，但具体类Mock在异常测试场景下已足够有效
         */
        $requestSender = $this->createMock(StunRequestSender::class);

        // 模拟传输异常
        $requestSender->expects($this->once())
            ->method('sendRequest')
            ->willThrowException(new TransportException('传输错误'))
        ;
        $requestSender->expects($this->once())
            ->method('log')
            ->with('warning', self::stringContains('测试III失败'))
        ;

        $executor = new StunTestExecutor($requestSender);
        $result = $executor->performTest3('stun.example.com', 3478);

        $this->assertFalse($result);
    }
}
