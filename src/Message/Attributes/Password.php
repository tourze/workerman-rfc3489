<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

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
     * @return self 当前实例，用于链式调用
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(): string
    {
        // 密码最大长度限制为128字节
        $password = substr($this->password, 0, Constants::MAX_PASSWORD_LENGTH);
        
        return $password;
    }

    /**
     * {@inheritdoc}
     */
    public static function decode(string $data, int $offset, int $length): static
    {
        $password = substr($data, $offset, $length);
        
        return new self($password);
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        return strlen(substr($this->password, 0, Constants::MAX_PASSWORD_LENGTH));
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        // 不直接显示密码，用星号替代
        $maskedPassword = str_repeat('*', min(strlen($this->password), 8));
        
        return sprintf('PASSWORD: %s (masked)', $maskedPassword);
    }
}
