<?php

namespace Tourze\Workerman\RFC3489\Tests\Message;

use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;

/**
 * 用于测试的具体 MessageAttribute 实现
 */
class ConcreteMessageAttribute extends MessageAttribute
{
    protected int $type;

    protected mixed $value = null;

    private int $specificLength = 0;

    public function __construct(AttributeType|int $type, mixed $value = null)
    {
        parent::__construct($type, $value);
    }

    public static function decode(string $data, int $offset, int $length): static
    {
        $attribute = new self(0);
        $attribute->setValue(substr($data, $offset, $length));

        return $attribute;
    }

    public function setType(int $type): void
    {
        $this->type = $type;
    }

    public function setLength(int $length): void
    {
        $this->specificLength = $length;
    }

    public function encode(): string
    {
        return $this->encodeAttributeHeader() . $this->getValue() . str_repeat("\x00", $this->getPadding());
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

    public function getLength(): int
    {
        if ($this->specificLength > 0) {
            return $this->specificLength;
        }

        return strlen((string) $this->getValue());
    }

    public function getPadding(): int
    {
        $length = $this->getLength();
        if (0 === $length % 4) {
            return 0;
        }

        return 4 - ($length % 4);
    }

    public function __toString(): string
    {
        $type = AttributeType::tryFrom($this->getType());
        $typeName = null !== $type ? $type->name : 'UNKNOWN';

        return sprintf(
            'Attribute: %s (0x%04X), Length: %d, Value: %s',
            $typeName,
            $this->getType(),
            $this->getLength(),
            bin2hex((string) $this->getValue())
        );
    }
}
