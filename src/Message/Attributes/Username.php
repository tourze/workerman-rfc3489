<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;

/**
 * USERNAME属性
 *
 * 用于消息完整性验证的用户名
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.6
 */
class Username extends MessageAttribute
{
    /**
     * 用户名
     */
    private string $username;

    /**
     * 创建一个新的USERNAME属性
     *
     * @param string $username 用户名
     */
    public function __construct(string $username)
    {
        parent::__construct(AttributeType::USERNAME);
        $this->username = $username;
    }

    /**
     * 获取用户名
     *
     * @return string 用户名
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * 设置用户名
     *
     * @param string $username 用户名
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function getValue(): string
    {
        return $this->username;
    }

    public function setValue(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Username属性值必须是字符串');
        }

        $this->username = $value;
    }

    public function encode(): string
    {
        // 用户名最大长度限制为512字节
        $username = substr($this->username, 0, Constants::MAX_USERNAME_LENGTH);

        // 编码属性头部和值
        $encoded = $this->encodeAttributeHeader() . $username;

        // 添加必要的填充字节
        $padding = $this->getPadding();
        if ($padding > 0) {
            $encoded .= str_repeat("\x00", $padding);
        }

        return $encoded;
    }

    public static function decode(string $data, int $offset, int $length): static
    {
        // 检查类型是否匹配
        $type = BinaryUtils::decodeUint16($data, $offset);
        if ($type !== AttributeType::USERNAME->value) {
            throw new InvalidArgumentException('无法解析USERNAME属性');
        }

        // 读取长度
        $valueLength = BinaryUtils::decodeUint16($data, $offset + 2);

        // 提取用户名
        $username = substr($data, $offset + 4, $valueLength);

        // @phpstan-ignore new.static
        return new static($username);
    }

    public function getLength(): int
    {
        // 用户名长度，限制最大值
        return strlen(substr($this->username, 0, Constants::MAX_USERNAME_LENGTH));
    }

    public function __toString(): string
    {
        $type = AttributeType::tryFrom($this->getType());
        $typeName = null !== $type ? $type->name : 'UNKNOWN';

        return sprintf(
            '%s (0x%04X): Length=%d, Value=%s',
            $typeName,
            $this->getType(),
            $this->getLength(),
            $this->username
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
