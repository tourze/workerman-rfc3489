<?php

namespace Tourze\Workerman\RFC3489\Message;

use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangeRequest;
use Tourze\Workerman\RFC3489\Message\Attributes\ErrorCodeAttribute;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\MessageIntegrity;
use Tourze\Workerman\RFC3489\Message\Attributes\Password;
use Tourze\Workerman\RFC3489\Message\Attributes\ReflectedFrom;
use Tourze\Workerman\RFC3489\Message\Attributes\ResponseAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\SourceAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\UnknownAttributes;
use Tourze\Workerman\RFC3489\Message\Attributes\Username;

/**
 * STUN消息类
 *
 * 实现RFC3489定义的STUN消息结构，包括头部和属性
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10 消息格式定义
 */
class StunMessage
{
    /**
     * 消息类型，由消息方法和消息类别组成
     */
    protected int $messageType;

    /**
     * 消息长度（不包括头部）
     */
    protected int $messageLength = 0;

    /**
     * 事务ID，用于匹配请求和响应
     */
    protected string $transactionId;

    /**
     * 消息属性列表
     *
     * @var MessageAttribute[]
     */
    protected array $attributes = [];

    /**
     * 创建一个新的STUN消息
     *
     * @param int         $messageType   消息类型
     * @param string|null $transactionId 事务ID，如果为null会自动生成
     */
    public function __construct(int $messageType, ?string $transactionId = null)
    {
        $this->messageType = $messageType;
        $this->transactionId = $transactionId ?? $this->generateTransactionId();
    }

    /**
     * 获取消息类型
     *
     * @return int 消息类型值
     */
    public function getMessageType(): int
    {
        return $this->messageType;
    }

    /**
     * 设置消息类型
     *
     * @param int $messageType 消息类型值
     */
    public function setMessageType(int $messageType): void
    {
        $this->messageType = $messageType;
    }

    /**
     * 获取消息方法
     *
     * @return MessageMethod|null 消息方法枚举
     */
    public function getMethod(): ?MessageMethod
    {
        return MessageMethod::fromMessageType($this->messageType);
    }

    /**
     * 获取消息类别
     *
     * @return MessageClass|null 消息类别枚举
     */
    public function getClass(): ?MessageClass
    {
        return MessageClass::fromMessageType($this->messageType);
    }

    /**
     * 获取消息长度
     *
     * @return int 消息长度（不包括头部）
     */
    public function getMessageLength(): int
    {
        return $this->calculateMessageLength();
    }

    /**
     * 获取事务ID
     *
     * @return string 事务ID
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    /**
     * 设置事务ID
     *
     * @param string $transactionId 事务ID
     */
    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    /**
     * 添加一个属性
     *
     * @param MessageAttribute $attribute 属性实例
     *
     * @return self 当前实例，用于链式调用
     */
    public function addAttribute(MessageAttribute $attribute): self
    {
        $this->attributes[] = $attribute;

        return $this;
    }

    /**
     * 获取所有属性
     *
     * @return MessageAttribute[] 属性列表
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * 获取指定类型的属性
     *
     * @param AttributeType $type 属性类型
     *
     * @return MessageAttribute|null 找到的属性或null
     */
    public function getAttribute(AttributeType $type): ?MessageAttribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getType() === $type->value) {
                return $attribute;
            }
        }

        return null;
    }

    /**
     * 获取指定类型的所有属性
     *
     * @param AttributeType $type 属性类型
     *
     * @return MessageAttribute[] 属性列表
     */
    public function getAttributesByType(AttributeType $type): array
    {
        $result = [];

        foreach ($this->attributes as $attribute) {
            if ($attribute->getType() === $type->value) {
                $result[] = $attribute;
            }
        }

        return $result;
    }

    /**
     * 检查是否包含指定类型的属性
     *
     * @param AttributeType $type 属性类型
     *
     * @return bool 是否包含
     */
    public function hasAttribute(AttributeType $type): bool
    {
        return null !== $this->getAttribute($type);
    }

    /**
     * 从二进制数据解码STUN消息
     *
     * @param string $data 二进制数据
     *
     * @return self 解码后的消息实例
     *
     * @throws MessageFormatException 如果解码失败
     */
    public static function decode(string $data): self
    {
        $header = self::decodeHeader($data);
        $message = new self($header['type'], $header['transaction']);

        self::validateMessageLength($data, $header['length']);
        self::decodeAttributes($data, $message, $header['length']);

        return $message;
    }

    /**
     * 解码消息头部
     *
     * @param string $data 二进制数据
     *
     * @return array{type: int, length: int, transaction: string} 解码后的头部信息
     *
     * @throws MessageFormatException 如果解码失败
     */
    private static function decodeHeader(string $data): array
    {
        if (strlen($data) < Constants::HEADER_LENGTH) {
            throw new MessageFormatException('数据太短，无法解析STUN消息头部');
        }

        $header = unpack('ntype/nlength/a16transaction', $data);

        if (false === $header) {
            throw new MessageFormatException('无法解析STUN消息头部');
        }

        return [
            'type' => $header['type'],
            'length' => $header['length'],
            'transaction' => $header['transaction'],
        ];
    }

    /**
     * 验证消息长度
     *
     * @param string $data           二进制数据
     * @param int    $expectedLength 期望的长度
     *
     * @throws MessageFormatException 如果长度不足
     */
    private static function validateMessageLength(string $data, int $expectedLength): void
    {
        if (strlen($data) < Constants::HEADER_LENGTH + $expectedLength) {
            throw new MessageFormatException('消息长度不足');
        }
    }

    /**
     * 解码消息属性
     *
     * @param string $data             二进制数据
     * @param self   $message          消息实例
     * @param int    $attributesLength 属性数据长度
     *
     * @throws MessageFormatException 如果解码失败
     */
    private static function decodeAttributes(string $data, self $message, int $attributesLength): void
    {
        $offset = Constants::HEADER_LENGTH;
        $endOffset = $offset + $attributesLength;

        while ($offset < $endOffset) {
            $offset = self::decodeAttribute($data, $message, $offset, $endOffset);
        }
    }

    /**
     * 解码单个属性
     *
     * @param string $data      二进制数据
     * @param self   $message   消息实例
     * @param int    $offset    当前偏移量
     * @param int    $endOffset 结束偏移量
     *
     * @return int 下一个属性的偏移量
     *
     * @throws MessageFormatException 如果解码失败
     */
    private static function decodeAttribute(string $data, self $message, int $offset, int $endOffset): int
    {
        if ($offset + 4 > $endOffset) {
            throw new MessageFormatException('属性头部不完整');
        }

        $attrHeader = unpack('ntype/nlength', substr($data, $offset, 4));

        if (false === $attrHeader) {
            throw new MessageFormatException('无法解析属性头部');
        }

        $attrType = $attrHeader['type'];
        $attrLength = $attrHeader['length'];

        if ($offset + 4 + $attrLength > $endOffset) {
            throw new MessageFormatException('属性数据不完整');
        }

        self::createAndAddAttribute($data, $message, $offset, $attrType, $attrLength);

        // 移动到下一个属性，考虑4字节对齐
        $padding = ($attrLength % 4) !== 0 ? 4 - ($attrLength % 4) : 0;

        return $offset + 4 + $attrLength + $padding;
    }

    /**
     * 创建并添加属性
     *
     * @param string $data       二进制数据
     * @param self   $message    消息实例
     * @param int    $offset     属性偏移量
     * @param int    $attrType   属性类型
     * @param int    $attrLength 属性长度
     */
    private static function createAndAddAttribute(string $data, self $message, int $offset, int $attrType, int $attrLength): void
    {
        try {
            $attributeType = AttributeType::tryFrom($attrType);
            if (null !== $attributeType) {
                $attribute = self::createAttributeFromType($attrType, $data, $offset, $attrLength);
                if (null !== $attribute) {
                    $message->addAttribute($attribute);
                }
            }
        } catch (\Throwable $e) {
            // 如果解析特定属性失败，记录错误但继续解析其他属性
        }
    }

    /**
     * 将消息编码为二进制数据
     *
     * @return string 编码后的二进制数据
     */
    public function encode(): string
    {
        $attributes = '';

        // 编码所有属性
        foreach ($this->attributes as $attribute) {
            $encoded = $attribute->encode();
            $attrLength = strlen($encoded);

            // 添加属性头部(类型+长度)
            $attributes .= pack('nn', $attribute->getType(), $attrLength);
            $attributes .= $encoded;

            // 添加填充以达到4字节对齐
            $padding = $attrLength % 4;
            if ($padding > 0) {
                $attributes .= str_repeat("\0", 4 - $padding);
            }
        }

        // 编码消息头部
        $messageLength = strlen($attributes);
        $header = pack('nna16', $this->messageType, $messageLength, $this->transactionId);

        return $header . $attributes;
    }

    /**
     * 计算消息长度（不包括头部）
     *
     * @return int 消息长度
     */
    protected function calculateMessageLength(): int
    {
        $length = 0;

        foreach ($this->attributes as $attribute) {
            $attrLength = $attribute->getLength();
            // 加上属性头部长度（4字节）
            $length += 4 + $attrLength;

            // 考虑4字节对齐的填充
            $padding = $attrLength % Constants::ATTRIBUTE_ALIGNMENT;
            if ($padding > 0) {
                $length += Constants::ATTRIBUTE_ALIGNMENT - $padding;
            }
        }

        return $length;
    }

    /**
     * 生成随机事务ID
     *
     * @return string 16字节的随机事务ID
     */
    protected function generateTransactionId(): string
    {
        return random_bytes(Constants::TRANSACTION_ID_LENGTH);
    }

    /**
     * 根据属性类型创建对应的属性实例
     *
     * @param int    $type   属性类型
     * @param string $data   二进制数据
     * @param int    $offset 起始偏移量
     * @param int    $length 数据长度
     *
     * @return MessageAttribute|null 属性实例或null
     */
    protected static function createAttributeFromType(int $type, string $data, int $offset, int $length): ?MessageAttribute
    {
        try {
            return match ($type) {
                AttributeType::MAPPED_ADDRESS->value => MappedAddress::decode($data, $offset, $length),
                AttributeType::RESPONSE_ADDRESS->value => ResponseAddress::decode($data, $offset, $length),
                AttributeType::CHANGE_REQUEST->value => ChangeRequest::decode($data, $offset, $length),
                AttributeType::SOURCE_ADDRESS->value => SourceAddress::decode($data, $offset, $length),
                AttributeType::CHANGED_ADDRESS->value => ChangedAddress::decode($data, $offset, $length),
                AttributeType::USERNAME->value => Username::decode($data, $offset, $length),
                AttributeType::PASSWORD->value => Password::decode($data, $offset, $length),
                AttributeType::MESSAGE_INTEGRITY->value => MessageIntegrity::decode($data, $offset, $length),
                AttributeType::ERROR_CODE->value => ErrorCodeAttribute::decode($data, $offset, $length),
                AttributeType::UNKNOWN_ATTRIBUTES->value => UnknownAttributes::decode($data, $offset, $length),
                AttributeType::REFLECTED_FROM->value => ReflectedFrom::decode($data, $offset, $length),
                default => null,
            };
        } catch (\Throwable $e) {
            // 解码失败，返回null
            return null;
        }
    }

    /**
     * 获取人类可读的消息表示
     *
     * @return string 消息的文本表示
     */
    public function __toString(): string
    {
        $method = $this->getMethod();
        $class = $this->getClass();

        $methodName = null !== $method ? $method->name : 'UNKNOWN';
        $className = null !== $class ? $class->name : 'UNKNOWN';
        $transactionId = bin2hex($this->transactionId);

        $result = "STUN {$methodName} {$className} (transaction-id: {$transactionId})\n";

        foreach ($this->attributes as $index => $attribute) {
            $result .= '  ' . ($index + 1) . '. ' . $attribute->__toString() . "\n";
        }

        return $result;
    }
}
