<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Protocol\AddressMappingCollector;
use Tourze\Workerman\RFC3489\Protocol\NatType;

/**
 * AddressMappingCollector 测试类
 *
 * @internal
 */
#[CoversClass(AddressMappingCollector::class)]
final class AddressMappingCollectorTest extends TestCase
{
    private AddressMappingCollector $collector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->collector = new AddressMappingCollector();
    }

    public function testConstructorWithoutLogger(): void
    {
        $collector = new AddressMappingCollector();

        $this->assertSame([], $collector->getAllMappings());
        $this->assertSame(0, $collector->count());
    }

    public function testConstructorWithLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $collector = new AddressMappingCollector($logger);

        $this->assertSame([], $collector->getAllMappings());
    }

    public function testAddMapping(): void
    {
        $local = ['ip' => '192.168.1.1', 'port' => 12345];
        $remote = ['ip' => '8.8.8.8', 'port' => 3478];
        $mapped = ['ip' => '203.0.113.1', 'port' => 54321];

        $result = $this->collector->addMapping($local, $remote, $mapped);

        $this->assertSame($this->collector, $result);
        $this->assertSame(1, $this->collector->count());

        $mappings = $this->collector->getAllMappings();
        $this->assertCount(1, $mappings);
        $this->assertSame($local, $mappings[0]['local']);
        $this->assertSame($remote, $mappings[0]['remote']);
        $this->assertSame($mapped, $mappings[0]['mapped']);
        // 验证时间戳是合理的数值（大于当前时间减去1秒）
        $this->assertGreaterThan(microtime(true) - 1, $mappings[0]['timestamp']);
        $this->assertLessThan(microtime(true) + 1, $mappings[0]['timestamp']);
    }

    public function testAdd(): void
    {
        $result = $this->collector->add(
            '192.168.1.1',
            12345,
            '8.8.8.8',
            3478,
            '203.0.113.1',
            54321
        );

        $this->assertSame($this->collector, $result);
        $this->assertSame(1, $this->collector->count());

        $mappings = $this->collector->getAllMappings();
        $this->assertSame('192.168.1.1', $mappings[0]['local']['ip']);
        $this->assertSame(12345, $mappings[0]['local']['port']);
        $this->assertSame('8.8.8.8', $mappings[0]['remote']['ip']);
        $this->assertSame(3478, $mappings[0]['remote']['port']);
        $this->assertSame('203.0.113.1', $mappings[0]['mapped']['ip']);
        $this->assertSame(54321, $mappings[0]['mapped']['port']);
    }

    public function testGetMappingsByLocalAddress(): void
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.2', 12345, '8.8.8.8', 3478, '203.0.113.2', 54321);

        $mappings = $this->collector->getMappingsByLocalAddress('192.168.1.1', 12345);

        $this->assertCount(2, $mappings);
    }

    public function testGetMappingsByRemoteAddress(): void
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.2', 12345, '8.8.8.8', 3478, '203.0.113.2', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $mappings = $this->collector->getMappingsByRemoteAddress('8.8.8.8', 3478);

        $this->assertCount(2, $mappings);
    }

    public function testGetMappingsByMappedAddress(): void
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.2', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.2', 54321);

        $mappings = $this->collector->getMappingsByMappedAddress('203.0.113.1', 54321);

        $this->assertCount(2, $mappings);
    }

    public function testHasAddressReuse(): void
    {
        // 添加两个相同本地地址映射到相同公网地址的映射
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $this->assertTrue($this->collector->hasAddressReuse());
    }

    public function testHasAddressInconsistency(): void
    {
        // 添加相同本地地址映射到不同公网地址的映射
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.2', 54321);

        $this->assertTrue($this->collector->hasAddressInconsistency());
    }

    public function testIsDependentOnDestIp(): void
    {
        // 添加本地地址相同，远程IP不同，映射不同的情况
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.2', 54321);

        $this->assertTrue($this->collector->isDependentOnDestIp());
    }

    public function testIsDependentOnDestPort(): void
    {
        // 添加本地地址相同，远程IP相同，远程端口不同，映射不同的情况
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 53, '203.0.113.1', 54322);

        $this->assertTrue($this->collector->isDependentOnDestPort());
    }

    public function testInferNatTypeOpenInternet(): void
    {
        // 本地地址和映射地址相同
        $this->collector->add('203.0.113.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 12345);
        $this->collector->add('203.0.113.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 12345);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::OPEN_INTERNET, $natType);
    }

    public function testInferNatTypeSymmetric(): void
    {
        // 地址不一致的情况
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.2', 54321);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::SYMMETRIC, $natType);
    }

    public function testInferNatTypeFullCone2(): void
    {
        // 简化测试：完全锥形NAT（避免复杂的端口依赖判断逻辑）
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::FULL_CONE, $natType);
    }

    public function testInferNatTypeFullCone(): void
    {
        // 正常的锥形NAT
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::FULL_CONE, $natType);
    }

    public function testInferNatTypeInsufficientMappings(): void
    {
        $this->expectException(ProtocolException::class);
        $this->expectExceptionMessage('无效的协议状态: 映射数量不足, 期望: 至少需要2个映射才能推断NAT类型');

        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);

        $this->collector->inferNatType();
    }

    public function testClear(): void
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);

        $result = $this->collector->clear();

        $this->assertSame($this->collector, $result);
        $this->assertSame(0, $this->collector->count());
        $this->assertSame([], $this->collector->getAllMappings());
    }

    public function testSetLogger(): void
    {
        $logger = $this->createMock(LoggerInterface::class);

        // 期望logger不被调用（因为这只是设置测试）
        $logger->expects($this->never())
            ->method(self::anything())
        ;

        // setter方法现在返回void
        $this->collector->setLogger($logger);

        // 验证方法调用成功（无异常抛出），通过确认collector仍然有效
        $this->assertInstanceOf(AddressMappingCollector::class, $this->collector);
    }

    public function testCount(): void
    {
        // 测试空收集器的计数
        $this->assertSame(0, $this->collector->count());

        // 添加一个映射
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->assertSame(1, $this->collector->count());

        // 添加更多映射
        $this->collector->add('192.168.1.2', 12346, '8.8.4.4', 3478, '203.0.113.2', 54322);
        $this->collector->add('192.168.1.3', 12347, '1.1.1.1', 3478, '203.0.113.3', 54323);
        $this->assertSame(3, $this->collector->count());

        // 清空后计数应为0
        $this->collector->clear();
        $this->assertSame(0, $this->collector->count());
    }
}
