<?php

namespace Tourze\Workerman\RFC3489\Message;

/**
 * STUN消息方法枚举
 * 
 * 定义了RFC3489中的STUN消息方法类型
 * 
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1 消息方法定义
 */
enum MessageMethod: int
{
    /**
     * Binding方法
     * 
     * 用于获取NAT后的公网地址和端口
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-9.1 Binding请求处理
     */
    case BINDING = 0x0001;
    
    /**
     * Shared Secret方法
     * 
     * 用于获取消息完整性验证的共享密钥
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-9.2 Shared Secret请求处理
     */
    case SHARED_SECRET = 0x0002;
    
    /**
     * 从消息类型获取消息方法
     * 
     * @param int $messageType 消息类型值
     * @return self|null 消息方法枚举或null
     */
    public static function fromMessageType(int $messageType): ?self
    {
        $value = $messageType & 0x3EEF;
        
        return match ($value) {
            self::BINDING->value => self::BINDING,
            self::SHARED_SECRET->value => self::SHARED_SECRET,
            default => null
        };
    }
    
    /**
     * 获取消息方法的可读名称
     * 
     * @return string 消息方法名称
     */
    public function getName(): string
    {
        return match ($this) {
            self::BINDING => 'Binding',
            self::SHARED_SECRET => 'Shared Secret'
        };
    }
}
