<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * MESSAGE-INTEGRITY属性
 *
 * 包含HMAC-SHA1消息完整性校验值
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.8
 */
class MessageIntegrity extends MessageAttribute
{
    /**
     * HMAC-SHA1哈希值
     */
    private string $hmac;

    /**
     * 创建一个新的MESSAGE-INTEGRITY属性
     *
     * @param string $hmac HMAC-SHA1哈希值，如果为null，则需要后续计算
     */
    public function __construct(?string $hmac = null)
    {
        parent::__construct(AttributeType::MESSAGE_INTEGRITY);
        $this->hmac = $hmac ?? str_repeat("\0", Constants::MESSAGE_INTEGRITY_LENGTH);
    }

    /**
     * 获取HMAC-SHA1哈希值
     *
     * @return string HMAC-SHA1哈希值
     */
    public function getHmac(): string
    {
        return $this->hmac;
    }

    /**
     * 设置HMAC-SHA1哈希值
     *
     * @param string $hmac HMAC-SHA1哈希值
     */
    public function setHmac(string $hmac): void
    {
        $this->hmac = $hmac;
    }

    /**
     * 计算消息的HMAC-SHA1哈希值
     *
     * @param string $message 完整的STUN消息（不包括此属性）
     * @param string $key     用于HMAC计算的密钥（通常是密码）
     *
     * @return string HMAC-SHA1哈希值
     */
    public static function calculateHmac(string $message, string $key): string
    {
        return hash_hmac('sha1', $message, $key, true);
    }

    /**
     * 使用给定的密钥计算并设置HMAC-SHA1哈希值
     *
     * @param string $message 完整的STUN消息（不包括此属性）
     * @param string $key     用于HMAC计算的密钥（通常是密码）
     *
     * @return self 当前实例，用于链式调用
     */
    public function calculateAndSetHmac(string $message, string $key): self
    {
        $this->hmac = self::calculateHmac($message, $key);

        return $this;
    }

    /**
     * 验证消息的完整性
     *
     * @param string $message 完整的STUN消息（不包括此属性）
     * @param string $key     用于HMAC计算的密钥（通常是密码）
     *
     * @return bool 如果验证通过，返回true
     */
    public function verify(string $message, string $key): bool
    {
        $calculatedHmac = self::calculateHmac($message, $key);

        return hash_equals($this->hmac, $calculatedHmac);
    }

    public function encode(): string
    {
        return $this->hmac;
    }

    public static function decode(string $data, int $offset, int $length): static
    {
        if (Constants::MESSAGE_INTEGRITY_LENGTH !== $length) {
            throw new InvalidArgumentException('MESSAGE-INTEGRITY属性长度必须是20字节');
        }

        $hmac = substr($data, $offset, Constants::MESSAGE_INTEGRITY_LENGTH);

        // @phpstan-ignore new.static
        return new static($hmac);
    }

    public function getLength(): int
    {
        return Constants::MESSAGE_INTEGRITY_LENGTH;
    }

    public function __toString(): string
    {
        return sprintf('MESSAGE-INTEGRITY: %s', bin2hex($this->hmac));
    }
}
