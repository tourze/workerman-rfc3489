<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * PASSWORD属性
 *
 * 用于Shared Secret响应中返回密码
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.7
 */
class Password extends MessageAttribute
{
    /**
     * 密码
     */
    private string $password;

    /**
     * 创建一个新的PASSWORD属性
     *
     * @param string $password 密码
     */
    public function __construct(string $password)
    {
        parent::__construct(AttributeType::PASSWORD);
        $this->password = $password;
    }

    /**
     * 获取密码
     *
     * @return string 密码
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * 设置密码
     *
     * @param string $password 密码
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /**
     * 获取密码值
     *
     * @return string 密码
     */
    public function getValue(): string
    {
        return $this->password;
    }

    /**
     * 设置密码值
     *
     * @param mixed $value 密码值
     *
     * @throws \InvalidArgumentException 如果值不是字符串
     */
    public function setValue(mixed $value): void
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Password属性值必须是字符串');
        }

        $this->password = $value;
    }

    public function encode(): string
    {
        // 密码最大长度限制为128字节
        $password = substr($this->password, 0, Constants::MAX_PASSWORD_LENGTH);

        // 编码属性头部和值
        $encoded = $this->encodeAttributeHeader() . $password;

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
        $typeData = unpack('n', substr($data, $offset, 2));
        if (false === $typeData) {
            throw new InvalidArgumentException('无法读取PASSWORD属性类型');
        }
        $type = $typeData[1];
        if ($type !== AttributeType::PASSWORD->value) {
            throw new InvalidArgumentException('无法解析PASSWORD属性');
        }

        // 读取长度
        $lengthData = unpack('n', substr($data, $offset + 2, 2));
        if (false === $lengthData) {
            throw new InvalidArgumentException('无法读取PASSWORD属性长度');
        }
        $valueLength = $lengthData[1];

        // 提取密码
        $password = substr($data, $offset + 4, $valueLength);
        return new self($password);
    }

    public function getLength(): int
    {
        return strlen(substr($this->password, 0, Constants::MAX_PASSWORD_LENGTH));
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
            str_repeat('*', min(strlen($this->password), 8)) . ' (masked)'
        );
    }

    /**
     * 编码属性头部
     *
     * @return string 属性头部的二进制数据
     */
    protected function encodeAttributeHeader(): string
    {
        return pack('nn', $this->getType(), $this->getLength());
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
