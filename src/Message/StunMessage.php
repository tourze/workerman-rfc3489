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
     * 
     * @var int
     */
    protected int $messageType;
    
    /**
     * 消息长度（不包括头部）
     * 
     * @var int
     */
    protected int $messageLength = 0;
    
    /**
     * 事务ID，用于匹配请求和响应
     * 
     * @var string
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
     * @param int $messageType 消息类型
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
     * @return self 当前实例，用于链式调用
     */
    public function setMessageType(int $messageType): self
    {
        $this->messageType = $messageType;
        return $this;
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
     * @return self 当前实例，用于链式调用
     */
    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }
    
    /**
     * 添加一个属性
     * 
     * @param MessageAttribute $attribute 属性实例
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
     * @return bool 是否包含
     */
    public function hasAttribute(AttributeType $type): bool
    {
        return $this->getAttribute($type) !== null;
    }
    
    /**
     * 从二进制数据解码STUN消息
     * 
     * @param string $data 二进制数据
     * @return self 解码后的消息实例
     * @throws MessageFormatException 如果解码失败
     */
    public static function decode(string $data): self
    {
        if (strlen($data) < Constants::HEADER_LENGTH) {
            throw new MessageFormatException('数据太短，无法解析STUN消息头部');
        }
        
        // 解析消息头部
        $header = unpack('ntype/nlength/a16transaction', $data);
        
        if (!$header) {
            throw new MessageFormatException('无法解析STUN消息头部');
        }
        
        $message = new self($header['type'], $header['transaction']);
        
        // 检查消息长度
        $expectedLength = $header['length'];
        if (strlen($data) < Constants::HEADER_LENGTH + $expectedLength) {
            throw new MessageFormatException('消息长度不足');
        }
        
        // 解析消息属性
        $offset = Constants::HEADER_LENGTH;
        $endOffset = $offset + $expectedLength;
        
        while ($offset < $endOffset) {
            if ($offset + 4 > $endOffset) {
                throw new MessageFormatException('属性头部不完整');
            }
            
            // 解析属性头部
            $attrHeader = unpack('ntype/nlength', substr($data, $offset, 4));
            $offset += 4;
            
            $attrType = $attrHeader['type'];
            $attrLength = $attrHeader['length'];
            
            if ($offset + $attrLength > $endOffset) {
                throw new MessageFormatException('属性数据不完整');
            }
            
            // 创建对应类型的属性实例
            $attribute = self::createAttributeFromType($attrType, $data, $offset, $attrLength);
            if ($attribute) {
                $message->addAttribute($attribute);
            }
            
            // 移动到下一个属性，考虑4字节对齐
            $padding = ($attrLength % Constants::ATTRIBUTE_ALIGNMENT) ? 
                        Constants::ATTRIBUTE_ALIGNMENT - ($attrLength % Constants::ATTRIBUTE_ALIGNMENT) : 0;
            $offset += $attrLength + $padding;
        }
        
        return $message;
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
            $attributes .= pack('nn', $attribute->getType(), strlen($encoded)) . $encoded;
            
            // 添加填充以达到4字节对齐
            $padding = strlen($encoded) % Constants::ATTRIBUTE_ALIGNMENT;
            if ($padding > 0) {
                $attributes .= str_repeat("\0", Constants::ATTRIBUTE_ALIGNMENT - $padding);
            }
        }
        
        // 编码消息头部
        $header = pack('nna16', $this->messageType, strlen($attributes), $this->transactionId);
        
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
     * @param int $type 属性类型
     * @param string $data 二进制数据
     * @param int $offset 起始偏移量
     * @param int $length 数据长度
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
                default => null
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
        $class = $this->getClass()?->getName() ?? 'Unknown';
        $method = $this->getMethod()?->getName() ?? 'Unknown';
        $transactionId = bin2hex($this->transactionId);
        
        $result = "STUN $method $class (transaction-id: $transactionId)\n";
        
        foreach ($this->attributes as $index => $attribute) {
            $result .= "  " . ($index + 1) . ". " . $attribute->__toString() . "\n";
        }
        
        return $result;
    }
}
