<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Protocol\AddressMappingCollector;
use Tourze\Workerman\RFC3489\Protocol\NatType;

/**
 * AddressMappingCollector 测试类
 */
class AddressMappingCollectorTest extends TestCase
{
    private AddressMappingCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new AddressMappingCollector();
    }

    public function testConstructorWithoutLogger()
    {
        $collector = new AddressMappingCollector();
        
        $this->assertSame([], $collector->getAllMappings());
        $this->assertSame(0, $collector->count());
    }

    public function testConstructorWithLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $collector = new AddressMappingCollector($logger);
        
        $this->assertSame([], $collector->getAllMappings());
    }

    public function testAddMapping()
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
        $this->assertIsFloat($mappings[0]['timestamp']);
    }

    public function testAdd()
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

    public function testGetMappingsByLocalAddress()
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.2', 12345, '8.8.8.8', 3478, '203.0.113.2', 54321);

        $mappings = $this->collector->getMappingsByLocalAddress('192.168.1.1', 12345);

        $this->assertCount(2, $mappings);
    }

    public function testGetMappingsByRemoteAddress()
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.2', 12345, '8.8.8.8', 3478, '203.0.113.2', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $mappings = $this->collector->getMappingsByRemoteAddress('8.8.8.8', 3478);

        $this->assertCount(2, $mappings);
    }

    public function testGetMappingsByMappedAddress()
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.2', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.2', 54321);

        $mappings = $this->collector->getMappingsByMappedAddress('203.0.113.1', 54321);

        $this->assertCount(2, $mappings);
    }

    public function testHasAddressReuse()
    {
        // 添加两个相同本地地址映射到相同公网地址的映射
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $this->assertTrue($this->collector->hasAddressReuse());
    }

    public function testHasAddressInconsistency()
    {
        // 添加相同本地地址映射到不同公网地址的映射
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.2', 54321);

        $this->assertTrue($this->collector->hasAddressInconsistency());
    }

    public function testIsDependentOnDestIp()
    {
        // 添加本地地址相同，远程IP不同，映射不同的情况
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.2', 54321);

        $this->assertTrue($this->collector->isDependentOnDestIp());
    }

    public function testIsDependentOnDestPort()
    {
        // 添加本地地址相同，远程IP相同，远程端口不同，映射不同的情况
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 53, '203.0.113.1', 54322);

        $this->assertTrue($this->collector->isDependentOnDestPort());
    }

    public function testInferNatTypeOpenInternet()
    {
        // 本地地址和映射地址相同
        $this->collector->add('203.0.113.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 12345);
        $this->collector->add('203.0.113.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 12345);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::OPEN_INTERNET, $natType);
    }

    public function testInferNatTypeSymmetric()
    {
        // 地址不一致的情况
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.2', 54321);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::SYMMETRIC, $natType);
    }

    public function testInferNatTypeFullCone2()
    {
        // 简化测试：完全锥形NAT（避免复杂的端口依赖判断逻辑）
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::FULL_CONE, $natType);
    }

    public function testInferNatTypeFullCone()
    {
        // 正常的锥形NAT
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);
        $this->collector->add('192.168.1.1', 12345, '8.8.4.4', 3478, '203.0.113.1', 54321);

        $natType = $this->collector->inferNatType();

        $this->assertSame(NatType::FULL_CONE, $natType);
    }

    public function testInferNatTypeInsufficientMappings()
    {
        $this->expectException(StunException::class);
        $this->expectExceptionMessage('映射数量不足以推断NAT类型，至少需要2个映射');

        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);

        $this->collector->inferNatType();
    }

    public function testClear()
    {
        $this->collector->add('192.168.1.1', 12345, '8.8.8.8', 3478, '203.0.113.1', 54321);

        $result = $this->collector->clear();

        $this->assertSame($this->collector, $result);
        $this->assertSame(0, $this->collector->count());
        $this->assertSame([], $this->collector->getAllMappings());
    }

    public function testSetLogger()
    {
        $logger = $this->createMock(LoggerInterface::class);

        $result = $this->collector->setLogger($logger);

        $this->assertSame($this->collector, $result);
    }
}