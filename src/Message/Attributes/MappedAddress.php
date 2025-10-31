<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;
use Tourze\Workerman\RFC3489\Utils\IpUtils;

/**
 * MAPPED-ADDRESS属性
 *
 * 包含STUN服务器看到的客户端的IP地址和端口（即NAT映射后的地址）
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.1
 */
class MappedAddress extends MessageAttribute
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
     * 创建一个新的MAPPED-ADDRESS属性
     *
     * @param string $ip   IP地址
     * @param int    $port 端口号
     */
    public function __construct(string $ip, int $port)
    {
        parent::__construct(AttributeType::MAPPED_ADDRESS);
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
     * 设置IP地址
     *
     * @param string $ip IP地址
     */
    public function setIp(string $ip): void
    {
        $this->ip = $ip;
        $this->family = IpUtils::getAddressFamily($ip);
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
     * 设置端口号
     *
     * @param int $port 端口号
     */
    public function setPort(int $port): void
    {
        $this->port = $port;
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

    public function getValue(): string
    {
        return IpUtils::encodeAddress($this->ip, $this->port);
    }

    public function setValue(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('MappedAddress属性值必须是字符串');
        }

        [$ip, $port] = IpUtils::decodeAddress($value, 0);

        if (null === $ip) {
            throw new InvalidArgumentException('无法解析地址值');
        }

        $this->ip = $ip;
        $this->port = $port;
        $this->family = IpUtils::getAddressFamily($ip);
    }

    public function encode(): string
    {
        // 编码属性头部和值
        return $this->encodeAttributeHeader() . $this->getValue() . str_repeat("\x00", $this->getPadding());
    }

    public static function decode(string $data, int $offset, int $length): static
    {
        // 检查类型是否匹配
        $type = BinaryUtils::decodeUint16($data, $offset);
        if ($type !== AttributeType::MAPPED_ADDRESS->value) {
            throw new InvalidArgumentException('无法解析MAPPED-ADDRESS属性');
        }

        // 跳过属性头部(4字节)
        $valueOffset = $offset + 4;

        try {
            [$ip, $port] = IpUtils::decodeAddress($data, $valueOffset);

            if (null === $ip) {
                throw new InvalidArgumentException('无法解析MAPPED-ADDRESS属性');
            }

            // @phpstan-ignore new.static
            return new static($ip, $port);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('无法解析MAPPED-ADDRESS属性');
        }
    }

    public function getLength(): int
    {
        // 地址族是IPv4还是IPv6，长度不同
        $ipLength = IpUtils::IPV4 === $this->family ? 4 : 16;

        // 1字节（保留）+ 1字节（地址族）+ 2字节（端口）+ IP地址长度
        return 4 + $ipLength;
    }

    public function __toString(): string
    {
        $type = AttributeType::tryFrom($this->getType());
        $typeName = null !== $type ? $type->name : 'UNKNOWN';

        return sprintf(
            '%s (0x%04X): %s',
            $typeName,
            $this->getType(),
            IpUtils::formatAddressPort($this->ip, $this->port)
        );
    }

    /**
     * 编码属性头部
     *
     * @return string 属性头部的二进制数据
     */
    protected function encodeAttributeHeader(): string
    {
        return BinaryUtils::encodeUint16($this->getType()) . BinaryUtils::encodeUint16($this->getLength());
    }

    /**
     * 获取填充字节数
     *
     * @return int 填充字节数
     */
    protected function getPadding(): int
    {
        $length = $this->getLength();
        if (0 === $length % 4) {
            return 0;
        }

        return 4 - ($length % 4);
    }
}
