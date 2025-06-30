<?php

namespace Tourze\Workerman\RFC3489\Message;

/**
 * STUN消息属性基类
 *
 * 所有STUN消息属性的抽象基类，定义了属性的基本结构和方法
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.1 属性格式定义
 */
abstract class MessageAttribute
{
    /**
     * 属性类型
     *
     * @var int
     */
    protected int $type;
    
    /**
     * 属性值
     *
     * @var mixed
     */
    protected mixed $value;
    
    /**
     * 创建一个新的属性实例
     *
     * @param AttributeType|int $type 属性类型，从AttributeType枚举获取
     * @param mixed $value 属性值
     */
    public function __construct(AttributeType|int $type, mixed $value = null)
    {
        $this->type = $type instanceof AttributeType ? $type->value : $type;
        $this->value = $value;
    }
    
    /**
     * 获取属性类型
     *
     * @return int 属性类型值
     */
    public function getType(): int
    {
        return $this->type;
    }
    
    /**
     * 获取属性类型枚举
     *
     * @return AttributeType|null 属性类型枚举
     */
    public function getTypeEnum(): ?AttributeType
    {
        return AttributeType::fromValue($this->type);
    }
    
    /**
     * 获取属性类型名称
     *
     * @return string 属性类型名称
     */
    public function getTypeName(): string
    {
        $type = $this->getTypeEnum();
        return $type?->getName() ?? 'UNKNOWN';
    }
    
    /**
     * 获取属性值
     *
     * @return mixed 属性值
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
    
    /**
     * 设置属性值
     *
     * @param mixed $value 属性值
     * @return self 当前实例，用于链式调用
     */
    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }
    
    /**
     * 将属性编码为二进制数据
     *
     * @return string 编码后的二进制数据
     */
    abstract public function encode(): string;
    
    /**
     * 从二进制数据解码属性
     *
     * @param string $data 二进制数据
     * @param int $offset 起始偏移量
     * @param int $length 数据长度
     * @return static 解码后的属性实例
     */
    abstract public static function decode(string $data, int $offset, int $length): static;
    
    /**
     * 获取属性的长度（字节数）
     *
     * @return int 属性长度
     */
    abstract public function getLength(): int;
    
    /**
     * 获取人类可读的属性表示
     *
     * @return string 属性的文本表示
     */
    abstract public function __toString(): string;
}
