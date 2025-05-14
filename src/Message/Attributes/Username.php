<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

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
     * @return self 当前实例，用于链式调用
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(): string
    {
        // 用户名最大长度限制为512字节
        $username = substr($this->username, 0, Constants::MAX_USERNAME_LENGTH);
        
        return $username;
    }

    /**
     * {@inheritdoc}
     */
    public static function decode(string $data, int $offset, int $length): static
    {
        $username = substr($data, $offset, $length);
        
        return new self($username);
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        return strlen(substr($this->username, 0, Constants::MAX_USERNAME_LENGTH));
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return sprintf('USERNAME: %s', $this->username);
    }
}
