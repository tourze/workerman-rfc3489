<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * UNKNOWN-ATTRIBUTES属性
 *
 * 在错误响应中列出服务器不理解的属性
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.10
 */
class UnknownAttributes extends MessageAttribute
{
    /**
     * 未知属性类型列表
     *
     * @var int[]
     */
    private array $attributes = [];

    /**
     * 创建一个新的UNKNOWN-ATTRIBUTES属性
     *
     * @param int[] $attributes 未知属性类型列表
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct(AttributeType::UNKNOWN_ATTRIBUTES);
        $this->attributes = $attributes;
    }

    /**
     * 获取未知属性类型列表
     *
     * @return int[] 未知属性类型列表
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * 添加未知属性类型
     *
     * @param int $attribute 未知属性类型
     * @return self 当前实例，用于链式调用
     */
    public function addAttribute(int $attribute): self
    {
        if (!in_array($attribute, $this->attributes, true)) {
            $this->attributes[] = $attribute;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function encode(): string
    {
        $result = '';

        foreach ($this->attributes as $attribute) {
            $result .= pack('n', $attribute);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public static function decode(string $data, int $offset, int $length): static
    {
        if ($length % 2 !== 0) {
            throw new InvalidArgumentException('UNKNOWN-ATTRIBUTES属性长度必须是2的倍数');
        }

        $count = $length / 2;
        $attributes = [];

        for ($i = 0; $i < $count; $i++) {
            $attrValue = unpack('n', substr($data, $offset + $i * 2, 2));

            if ($attrValue) {
                $attributes[] = $attrValue[1];
            }
        }

        return new static($attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getLength(): int
    {
        // 每个属性类型占用2个字节
        return count($this->attributes) * 2;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $attributeStrings = [];

        foreach ($this->attributes as $attribute) {
            $hexValue = sprintf('0x%04X', $attribute);
            $attrType = AttributeType::tryFrom($attribute);
            $name = $attrType !== null ? $attrType->getName() : '未知';

            $attributeStrings[] = "$hexValue ($name)";
        }

        return sprintf('UNKNOWN-ATTRIBUTES: %s', implode(', ', $attributeStrings));
    }
}
