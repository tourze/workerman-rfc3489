<?php

namespace Tourze\Workerman\RFC3489\Utils;

use Tourze\Workerman\RFC3489\Exception\StunIpException;

/**
 * IP地址处理工具类
 *
 * 提供IP地址相关的实用方法
 */
class IpUtils
{
    /**
     * IPv4地址族
     */
    public const IPV4 = 1;

    /**
     * IPv6地址族
     */
    public const IPV6 = 2;

    /**
     * 将IP地址和端口编码为STUN协议中的格式
     *
     * @param string $ip   IP地址
     * @param int    $port 端口号
     *
     * @return string 编码后的二进制数据
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.1 地址属性格式
     */
    public static function encodeAddress(string $ip, int $port): string
    {
        $family = self::getAddressFamily($ip);
        $result = "\x00"; // 第一个字节保留，必须为0
        $result .= chr($family); // 第二个字节是地址族
        $result .= pack('n', $port); // 端口（网络字节序）

        if (self::IPV4 === $family) {
            $result .= inet_pton($ip); // IP地址（网络字节序）
        } elseif (self::IPV6 === $family) {
            $result .= inet_pton($ip); // IPv6地址（网络字节序）
        }

        return $result;
    }

    /**
     * 从STUN协议格式解码IP地址和端口
     *
     * @param string $data   二进制数据
     * @param int    $offset 起始偏移量
     *
     * @return array{0: string|null, 1: int, 2: int} [ip, port, family]
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.1 地址属性格式
     */
    public static function decodeAddress(string $data, int $offset): array
    {
        // 忽略第一个字节（保留）
        $family = ord($data[$offset + 1]);
        $portData = unpack('n', substr($data, $offset + 2, 2));
        if (false === $portData) {
            throw new StunIpException('无法解析端口数据');
        }
        $port = $portData[1];

        if (self::IPV4 === $family) {
            $ip = inet_ntop(substr($data, $offset + 4, 4));
            if (false === $ip) {
                throw new StunIpException('无法解析IPv4地址');
            }

            return [$ip, $port, $family];
        }
        if (self::IPV6 === $family) {
            $ip = inet_ntop(substr($data, $offset + 4, 16));
            if (false === $ip) {
                throw new StunIpException('无法解析IPv6地址');
            }

            return [$ip, $port, $family];
        }

        return [null, $port, $family];
    }

    /**
     * 获取IP地址的地址族
     *
     * @param string $ip IP地址
     *
     * @return int 地址族常量
     */
    public static function getAddressFamily(string $ip): int
    {
        if (self::isIpv4($ip)) {
            return self::IPV4;
        }
        if (self::isIpv6($ip)) {
            return self::IPV6;
        }

        return 0; // 无效的IP地址
    }

    /**
     * 检查IP地址是否为IPv4
     *
     * @param string $ip IP地址
     *
     * @return bool 是否为IPv4
     */
    public static function isIpv4(string $ip): bool
    {
        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
    }

    /**
     * 检查IP地址是否为IPv6
     *
     * @param string $ip IP地址
     *
     * @return bool 是否为IPv6
     */
    public static function isIpv6(string $ip): bool
    {
        return false !== filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);
    }

    /**
     * 检查IP地址是否为私有地址
     *
     * @param string $ip IP地址
     *
     * @return bool 是否为私有地址
     */
    public static function isPrivateIp(string $ip): bool
    {
        return false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE);
    }

    /**
     * 获取本地IP地址
     *
     * @param bool $preferIpv4 是否优先返回IPv4地址
     *
     * @return string|null 本地IP地址或null
     */
    public static function getLocalIp(bool $preferIpv4 = true): ?string
    {
        // 方法1：使用socket连接获取IP
        $ip = self::getIpViaSocket();
        if (null !== $ip) {
            return $ip;
        }

        // 方法2：使用主机名获取IP
        $ip = self::getIpViaHostname();
        if (null !== $ip) {
            return $ip;
        }

        // 方法3：使用服务器变量
        return self::getIpViaServerVar();
    }

    /**
     * 通过socket连接获取本地IP地址
     *
     * @return string|null IP地址或null
     */
    private static function getIpViaSocket(): ?string
    {
        if (!function_exists('socket_get_status') || !function_exists('socket_create')) {
            return null;
        }

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (false === $socket) {
            return null;
        }

        $result = null;
        if (socket_connect($socket, '8.8.8.8', 53)) {
            socket_getsockname($socket, $ip);
            if (isset($ip) && false !== $ip) {
                $result = $ip;
            }
        }

        socket_close($socket);

        return $result;
    }

    /**
     * 通过主机名获取本地IP地址
     *
     * @return string|null IP地址或null
     */
    private static function getIpViaHostname(): ?string
    {
        if (!function_exists('gethostname') || !function_exists('gethostbyname')) {
            return null;
        }

        $hostName = gethostname();
        if (false === $hostName) {
            return null;
        }

        $ip = gethostbyname($hostName);

        return $ip !== $hostName ? $ip : null;
    }

    /**
     * 通过服务器变量获取本地IP地址
     *
     * @return string|null IP地址或null
     */
    private static function getIpViaServerVar(): ?string
    {
        return $_SERVER['SERVER_ADDR'] ?? null;
    }

    /**
     * 格式化IP地址和端口为字符串表示
     *
     * @param string $ip   IP地址
     * @param int    $port 端口号
     *
     * @return string 格式化后的字符串
     */
    public static function formatAddressPort(string $ip, int $port): string
    {
        if (self::isIpv6($ip)) {
            return "[{$ip}]:{$port}";
        }

        return "{$ip}:{$port}";
    }

    /**
     * 解析地址端口字符串为IP和端口
     *
     * @param string $addressPort 地址端口字符串，如"192.168.1.1:8080"或"[::1]:8080"
     *
     * @return array{0: string|null, 1: int|null} [ip, port]或[null, null]
     */
    public static function parseAddressPort(string $addressPort): array
    {
        // IPv6格式: [::1]:8080
        if (1 === preg_match('/^\[([^\]]+)\]:(\d+)$/', $addressPort, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        // IPv4格式: 192.168.1.1:8080
        if (1 === preg_match('/^([^:]+):(\d+)$/', $addressPort, $matches)) {
            return [$matches[1], (int) $matches[2]];
        }

        return [null, null];
    }

    /**
     * 比较两个IP地址是否相等
     *
     * 考虑IPv4/IPv6标准化格式和地址家族
     *
     * @param string $ip1 第一个IP地址
     * @param string $ip2 第二个IP地址
     *
     * @return bool 如果IP地址相等则返回true
     */
    public static function ipEquals(string $ip1, string $ip2): bool
    {
        // 如果字符串完全相同，直接返回true
        if ($ip1 === $ip2) {
            return true;
        }

        // 尝试将两个IP地址标准化后再比较
        $binary1 = @inet_pton($ip1);
        $binary2 = @inet_pton($ip2);

        // 如果转换失败，说明不是有效的IP地址
        if (false === $binary1 || false === $binary2) {
            return false;
        }

        // 比较二进制表示
        return $binary1 === $binary2;
    }
}
