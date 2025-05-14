<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

/**
 * REFLECTED-FROM属性
 *
 * 指示响应来源的客户端的IP地址，仅在响应中出现
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.11
 */
class ReflectedFrom extends MessageAttribute
{
    /**
     * IP地址
     */
    private string $ip;

    /**
     * 端口号
     */
    private int $port;

    /**
     * 地址族
     */
    private int $family;

    /**
     * 创建一个新的REFLECTED-FROM属性
     *
     * @param string $ip IP地址
     * @param int $port 端口号
     */
    public function __construct(string $ip, int $port)
    {
        parent::__construct(AttributeType::REFLECTED_FROM);
        $this->ip = $ip;
        $this->port = $port;
        $this->family = IpUtils::getAddressFamily($ip);
    }

    /**
     * 获取IP地址
     *
     * @return string IP地址
     */
    public function getIp(): string
    {
        return $this->ip;
    }

    /**
     * 获取端口号
     *
     * @return int 端口号
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * 获取地址族
     *
     * @return int 地址族常量
     */
    public function getFamily(): int
    {
        return $this->family;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(): string
    {
        return IpUtils::encodeAddress($this->ip, $this->port);
    }

    /**
     * {@inheritdoc}
     */
    public static function decode(string $data, int $offset, int $length): static
    {
        [$ip, $port] = IpUtils::decodeAddress($data, $offset);
        
        if ($ip === null) {
            throw new \InvalidArgumentException('无法解析REFLECTED-FROM属性');
        }
        
        return new self($ip, $port);
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        // 地址族是IPv4还是IPv6，长度不同
        $ipLength = $this->family === IpUtils::IPV4 ? 4 : 16;
        
        // 1字节（保留）+ 1字节（地址族）+ 2字节（端口）+ IP地址长度
        return 4 + $ipLength;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf('REFLECTED-FROM: %s', IpUtils::formatAddressPort($this->ip, $this->port));
    }
}
