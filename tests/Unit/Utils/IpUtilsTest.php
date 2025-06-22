<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

class IpUtilsTest extends TestCase
{
    public function testGetAddressFamilyWithIpv4()
    {
        $family = IpUtils::getAddressFamily('192.168.1.1');
        $this->assertSame(IpUtils::IPV4, $family); // IPv4 家族值为 1
    }

    public function testGetAddressFamilyWithIpv6()
    {
        $family = IpUtils::getAddressFamily('2001:db8::1');
        $this->assertSame(IpUtils::IPV6, $family); // IPv6 家族值为 2
    }

    public function testGetAddressFamilyWithInvalidAddress()
    {
        $family = IpUtils::getAddressFamily('invalid-ip');
        $this->assertSame(0, $family); // 无效地址返回 0
    }

    public function testEncodeAddress_Ipv4()
    {
        $ip = '192.168.1.1';
        $port = 8080;
        $encoded = IpUtils::encodeAddress($ip, $port);

        $this->assertSame(8, strlen($encoded));

        // 检查地址家族是否为 IPv4 (1)
        $family = ord($encoded[1]);
        $this->assertSame(IpUtils::IPV4, $family);

        // 检查端口是否正确编码 (8080 = 0x1F90)
        $this->assertSame("\x1F\x90", substr($encoded, 2, 2));

        // 检查IP地址是否正确编码 (192.168.1.1 = 0xC0A80101)
        $this->assertSame("\xC0\xA8\x01\x01", substr($encoded, 4, 4));
    }

    public function testEncodeAddress_Ipv6()
    {
        $ip = '2001:db8::1';
        $port = 8080;
        $encoded = IpUtils::encodeAddress($ip, $port);

        $this->assertSame(20, strlen($encoded));

        // 检查地址家族是否为 IPv6 (2)
        $family = ord($encoded[1]);
        $this->assertSame(IpUtils::IPV6, $family);

        // 检查端口是否正确编码 (8080 = 0x1F90)
        $this->assertSame("\x1F\x90", substr($encoded, 2, 2));
    }

    public function testDecodeAddress_Ipv4()
    {
        $binary = "\x00\x01\x1F\x90\xC0\xA8\x01\x01";
        list($ip, $port, $family) = IpUtils::decodeAddress($binary, 0);

        $this->assertSame('192.168.1.1', $ip);
        $this->assertSame(8080, $port);
        $this->assertSame(IpUtils::IPV4, $family); // IPv4
    }

    public function testDecodeAddress_Ipv6()
    {
        // 创建一个 IPv6 地址二进制数据样例
        $ipv6Binary = "\x00\x02\x1F\x90" . inet_pton('2001:db8::1');
        list($ip, $port, $family) = IpUtils::decodeAddress($ipv6Binary, 0);

        $this->assertSame('2001:db8::1', $ip);
        $this->assertSame(8080, $port);
        $this->assertSame(IpUtils::IPV6, $family); // IPv6
    }

    public function testDecodeAddress_WithOffset()
    {
        $binary = "\x00\x00\x00\x01\x1F\x90\xC0\xA8\x01\x01";
        list($ip, $port, $family) = IpUtils::decodeAddress($binary, 2);

        $this->assertSame('192.168.1.1', $ip);
        $this->assertSame(8080, $port);
    }

    public function testDecodeAddress_WithInvalidFamily()
    {
        $binary = "\x00\x03\x1F\x90\xC0\xA8\x01\x01"; // 家族值为3（无效）
        list($ip, $port, $family) = IpUtils::decodeAddress($binary, 0);

        $this->assertNull($ip);
        $this->assertSame(8080, $port);
        $this->assertSame(3, $family);
    }

    public function testIsIpv4_WithValidAddress()
    {
        $this->assertTrue(IpUtils::isIpv4('192.168.1.1'));
        $this->assertTrue(IpUtils::isIpv4('8.8.8.8'));
        $this->assertTrue(IpUtils::isIpv4('127.0.0.1'));
        $this->assertTrue(IpUtils::isIpv4('0.0.0.0'));
        $this->assertTrue(IpUtils::isIpv4('255.255.255.255'));
    }

    public function testIsIpv4_WithInvalidAddress()
    {
        $this->assertFalse(IpUtils::isIpv4('256.0.0.1')); // 超出范围
        $this->assertFalse(IpUtils::isIpv4('1.2.3')); // 格式不正确
        $this->assertFalse(IpUtils::isIpv4('a.b.c.d')); // 非数字
        $this->assertFalse(IpUtils::isIpv4('2001:db8::1')); // IPv6
        $this->assertFalse(IpUtils::isIpv4('')); // 空字符串
    }

    public function testIsIpv6_WithValidAddress()
    {
        $this->assertTrue(IpUtils::isIpv6('2001:db8::1'));
        $this->assertTrue(IpUtils::isIpv6('::1')); // localhost
        $this->assertTrue(IpUtils::isIpv6('fe80::1234:5678:abcd:ef12'));
        $this->assertTrue(IpUtils::isIpv6('2001:0db8:85a3:0000:0000:8a2e:0370:7334'));
        $this->assertTrue(IpUtils::isIpv6('2001:db8:85a3::8a2e:370:7334')); // 压缩形式
    }

    public function testIsIpv6_WithInvalidAddress()
    {
        $this->assertFalse(IpUtils::isIpv6('192.168.1.1')); // IPv4
        $this->assertFalse(IpUtils::isIpv6('g001:db8::1')); // 非十六进制字符
        $this->assertFalse(IpUtils::isIpv6('2001:db8::1::2')); // 多个压缩组
        $this->assertFalse(IpUtils::isIpv6('')); // 空字符串
    }

    public function testIsPrivateIp()
    {
        $this->assertTrue(IpUtils::isPrivateIp('192.168.1.1'));
        $this->assertTrue(IpUtils::isPrivateIp('10.0.0.1'));
        $this->assertTrue(IpUtils::isPrivateIp('172.16.0.1'));
        $this->assertFalse(IpUtils::isPrivateIp('8.8.8.8'));
        $this->assertTrue(IpUtils::isPrivateIp('fd00::1')); // IPv6 私有地址
    }

    public function testGetLocalIp()
    {
        // 这个测试依赖于系统环境，所以我们只检查返回值类型
        $ip = IpUtils::getLocalIp();
        if ($ip !== null) {
            $this->assertTrue(IpUtils::isIpv4($ip) || IpUtils::isIpv6($ip));
        } else {
            // 当ip为null时，无需额外断言，因为已经通过if检查确认了
            $this->assertTrue(true, 'IP is null as expected');
        }
    }

    public function testFormatAddressPort()
    {
        $this->assertSame('192.168.1.1:8080', IpUtils::formatAddressPort('192.168.1.1', 8080));
        $this->assertSame('[2001:db8::1]:8080', IpUtils::formatAddressPort('2001:db8::1', 8080));
    }

    public function testParseAddressPort()
    {
        list($ip, $port) = IpUtils::parseAddressPort('192.168.1.1:8080');
        $this->assertSame('192.168.1.1', $ip);
        $this->assertSame(8080, $port);

        list($ip, $port) = IpUtils::parseAddressPort('[2001:db8::1]:8080');
        $this->assertSame('2001:db8::1', $ip);
        $this->assertSame(8080, $port);

        list($ip, $port) = IpUtils::parseAddressPort('invalid');
        $this->assertNull($ip);
        $this->assertNull($port);
    }

    public function testIpEquals()
    {
        $this->assertTrue(IpUtils::ipEquals('192.168.1.1', '192.168.1.1'));
        $this->assertTrue(IpUtils::ipEquals('2001:db8::1', '2001:db8::1'));
        $this->assertTrue(IpUtils::ipEquals('2001:0db8:0000:0000:0000:0000:0000:0001', '2001:db8::1'));
        $this->assertFalse(IpUtils::ipEquals('192.168.1.1', '192.168.1.2'));
        $this->assertFalse(IpUtils::ipEquals('2001:db8::1', '2001:db8::2'));
        $this->assertFalse(IpUtils::ipEquals('192.168.1.1', '2001:db8::1'));
        $this->assertFalse(IpUtils::ipEquals('invalid', '192.168.1.1'));
    }
}
