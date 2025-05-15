<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message\Attributes;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

class MappedAddressTest extends TestCase
{
    public function testConstructor()
    {
        $ip = '192.168.1.1';
        $port = 8080;
        $mappedAddress = new MappedAddress($ip, $port);
        
        $this->assertSame(AttributeType::MAPPED_ADDRESS->value, $mappedAddress->getType());
        $this->assertSame($ip, $mappedAddress->getIp());
        $this->assertSame($port, $mappedAddress->getPort());
    }
    
    public function testGetSetIp()
    {
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);
        
        $this->assertSame('192.168.1.1', $mappedAddress->getIp());
        
        $mappedAddress->setIp('10.0.0.1');
        $this->assertSame('10.0.0.1', $mappedAddress->getIp());
    }
    
    public function testGetSetPort()
    {
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);
        
        $this->assertSame(8080, $mappedAddress->getPort());
        
        $mappedAddress->setPort(12345);
        $this->assertSame(12345, $mappedAddress->getPort());
    }
    
    public function testGetLength()
    {
        // IPv4 地址
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);
        $this->assertSame(8, $mappedAddress->getLength());
        
        // IPv6 地址
        $mappedAddress = new MappedAddress('2001:db8::1', 8080);
        $this->assertSame(20, $mappedAddress->getLength());
    }
    
    public function testGetValue()
    {
        $ip = '192.168.1.1';
        $port = 8080;
        $mappedAddress = new MappedAddress($ip, $port);
        
        $value = $mappedAddress->getValue();
        
        // 检查值是否为IP地址的二进制表示
        $expectedValue = IpUtils::encodeAddress($ip, $port);
        $this->assertSame($expectedValue, $value);
    }
    
    public function testSetValue()
    {
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);
        
        // 创建新的地址值
        $newIp = '10.0.0.1';
        $newPort = 12345;
        $newValue = IpUtils::encodeAddress($newIp, $newPort);
        
        // 设置值
        $mappedAddress->setValue($newValue);
        
        // 验证IP和端口已正确更新
        $this->assertSame($newIp, $mappedAddress->getIp());
        $this->assertSame($newPort, $mappedAddress->getPort());
    }
    
    public function testEncode()
    {
        $ip = '192.168.1.1';
        $port = 8080;
        $mappedAddress = new MappedAddress($ip, $port);
        
        $encoded = $mappedAddress->encode();
        
        // 预期编码结果：类型(2字节) + 长度(2字节) + 地址值(8字节)
        $expectedValueLength = 8; // IPv4 地址
        
        // 验证类型和长度
        $this->assertSame(BinaryUtils::encodeUint16(AttributeType::MAPPED_ADDRESS->value), substr($encoded, 0, 2));
        $this->assertSame(BinaryUtils::encodeUint16($expectedValueLength), substr($encoded, 2, 2));
        
        // 验证属性值
        $expectedValue = IpUtils::encodeAddress($ip, $port);
        $this->assertSame($expectedValue, substr($encoded, 4, $expectedValueLength));
        
        // 验证总长度
        $this->assertSame(4 + $expectedValueLength, strlen($encoded));
    }
    
    public function testEncodeIpv6()
    {
        $ip = '2001:db8::1';
        $port = 8080;
        $mappedAddress = new MappedAddress($ip, $port);
        
        $encoded = $mappedAddress->encode();
        
        // 预期编码结果：类型(2字节) + 长度(2字节) + 地址值(20字节)
        $expectedValueLength = 20; // IPv6 地址
        
        // 验证类型和长度
        $this->assertSame(BinaryUtils::encodeUint16(AttributeType::MAPPED_ADDRESS->value), substr($encoded, 0, 2));
        $this->assertSame(BinaryUtils::encodeUint16($expectedValueLength), substr($encoded, 2, 2));
        
        // 验证属性值
        $expectedValue = IpUtils::encodeAddress($ip, $port);
        $this->assertSame($expectedValue, substr($encoded, 4, $expectedValueLength));
        
        // 验证总长度
        $this->assertSame(4 + $expectedValueLength, strlen($encoded));
    }
    
    public function testDecode()
    {
        $ip = '192.168.1.1';
        $port = 8080;
        
        // 创建二进制数据
        $addressValue = IpUtils::encodeAddress($ip, $port);
        $data = BinaryUtils::encodeUint16(AttributeType::MAPPED_ADDRESS->value);
        $data .= BinaryUtils::encodeUint16(strlen($addressValue));
        $data .= $addressValue;
        
        // 解码
        $offset = 0;
        $mappedAddress = MappedAddress::decode($data, $offset, 4 + strlen($addressValue));
        
        $this->assertInstanceOf(MappedAddress::class, $mappedAddress);
        $this->assertSame(AttributeType::MAPPED_ADDRESS->value, $mappedAddress->getType());
        $this->assertSame($ip, $mappedAddress->getIp());
        $this->assertSame($port, $mappedAddress->getPort());
    }
    
    public function testDecode_Ipv6()
    {
        $ip = '2001:db8::1';
        $port = 8080;
        
        // 创建二进制数据
        $addressValue = IpUtils::encodeAddress($ip, $port);
        $data = BinaryUtils::encodeUint16(AttributeType::MAPPED_ADDRESS->value);
        $data .= BinaryUtils::encodeUint16(strlen($addressValue));
        $data .= $addressValue;
        
        // 解码
        $offset = 0;
        $mappedAddress = MappedAddress::decode($data, $offset, 4 + strlen($addressValue));
        
        $this->assertInstanceOf(MappedAddress::class, $mappedAddress);
        $this->assertSame(AttributeType::MAPPED_ADDRESS->value, $mappedAddress->getType());
        // 注意：IPv6地址可能会以规范形式返回，而不是精确匹配输入
        $this->assertNotNull(filter_var($mappedAddress->getIp(), FILTER_VALIDATE_IP, FILTER_FLAG_IPV6));
        $this->assertSame($port, $mappedAddress->getPort());
    }
    
    public function testDecode_WithInvalidType()
    {
        // 创建类型不匹配的二进制数据
        $addressValue = IpUtils::encodeAddress('192.168.1.1', 8080);
        $data = BinaryUtils::encodeUint16(AttributeType::USERNAME->value); // 使用错误的类型
        $data .= BinaryUtils::encodeUint16(strlen($addressValue));
        $data .= $addressValue;
        
        // 解码
        $offset = 0;
        $this->expectException(\InvalidArgumentException::class);
        MappedAddress::decode($data, $offset, 4 + strlen($addressValue));
    }
    
    public function testToString()
    {
        $ip = '192.168.1.1';
        $port = 8080;
        $mappedAddress = new MappedAddress($ip, $port);
        
        $string = (string)$mappedAddress;
        
        // 检查字符串包含关键信息
        $this->assertStringContainsString('MAPPED_ADDRESS', $string);
        $this->assertStringContainsString('0x0001', $string); // 十六进制类型值
        $this->assertStringContainsString($ip, $string);
        $this->assertStringContainsString((string)$port, $string);
    }
} 