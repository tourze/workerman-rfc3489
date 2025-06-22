<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * CHANGE-REQUEST属性
 *
 * 请求服务器在不同的IP和/或端口发送响应
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.3
 */
class ChangeRequest extends MessageAttribute
{
    /**
     * 更改IP地址位掩码
     */
    public const CHANGE_IP = 0x04;

    /**
     * 更改端口位掩码
     */
    public const CHANGE_PORT = 0x02;

    /**
     * 是否更改IP地址
     */
    private bool $changeIp;

    /**
     * 是否更改端口
     */
    private bool $changePort;

    /**
     * 创建一个新的CHANGE-REQUEST属性
     *
     * @param bool $changeIp 是否更改IP地址
     * @param bool $changePort 是否更改端口
     */
    public function __construct(bool $changeIp = false, bool $changePort = false)
    {
        parent::__construct(AttributeType::CHANGE_REQUEST);
        $this->changeIp = $changeIp;
        $this->changePort = $changePort;
    }

    /**
     * 是否更改IP地址
     *
     * @return bool 是否更改IP地址
     */
    public function isChangeIp(): bool
    {
        return $this->changeIp;
    }

    /**
     * 是否更改端口
     *
     * @return bool 是否更改端口
     */
    public function isChangePort(): bool
    {
        return $this->changePort;
    }

    /**
     * 设置是否更改IP地址
     *
     * @param bool $changeIp 是否更改IP地址
     * @return self 当前实例，用于链式调用
     */
    public function setChangeIp(bool $changeIp): self
    {
        $this->changeIp = $changeIp;
        return $this;
    }

    /**
     * 设置是否更改端口
     *
     * @param bool $changePort 是否更改端口
     * @return self 当前实例，用于链式调用
     */
    public function setChangePort(bool $changePort): self
    {
        $this->changePort = $changePort;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(): string
    {
        $flags = 0;

        if ($this->changeIp) {
            $flags |= self::CHANGE_IP;
        }

        if ($this->changePort) {
            $flags |= self::CHANGE_PORT;
        }

        return "\x00\x00\x00" . chr($flags);
    }

    /**
     * {@inheritdoc}
     */
    public static function decode(string $data, int $offset, int $length): static
    {
        if ($length < 4) {
            throw new \InvalidArgumentException('CHANGE-REQUEST属性长度不足');
        }

        $flags = ord($data[$offset + 3]);

        $changeIp = ($flags & self::CHANGE_IP) !== 0;
        $changePort = ($flags & self::CHANGE_PORT) !== 0;

        return new static($changeIp, $changePort);
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        return 4; // 固定长度4字节
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $changes = [];

        if ($this->changeIp) {
            $changes[] = 'IP';
        }

        if ($this->changePort) {
            $changes[] = 'Port';
        }

        return sprintf('CHANGE-REQUEST: %s', empty($changes) ? 'None' : implode(', ', $changes));
    }
}
