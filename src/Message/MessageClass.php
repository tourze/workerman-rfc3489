<?php

namespace Tourze\Workerman\RFC3489\Message;

/**
 * STUN消息类别枚举
 * 
 * 定义了STUN消息的类别：请求、响应和错误响应
 * 
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1 消息类型定义
 */
enum MessageClass: int
{
    /**
     * 请求消息
     * 
     * 客户端向服务器发送的请求消息
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    case REQUEST = 0x0000;
    
    /**
     * 成功响应消息
     * 
     * 服务器对客户端请求的成功响应
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    case RESPONSE = 0x0100;
    
    /**
     * 错误响应消息
     * 
     * 服务器对客户端请求的错误响应
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    case ERROR_RESPONSE = 0x0110;
    
    /**
     * 从消息类型获取消息类别
     * 
     * @param int $messageType 消息类型值
     * @return self 消息类别枚举
     */
    public static function fromMessageType(int $messageType): ?self
    {
        $value = $messageType & 0x0110;
        
        return match ($value) {
            self::REQUEST->value => self::REQUEST,
            self::RESPONSE->value => self::RESPONSE,
            self::ERROR_RESPONSE->value => self::ERROR_RESPONSE,
            default => null
        };
    }
    
    /**
     * 获取消息类别的可读名称
     * 
     * @return string 消息类别名称
     */
    public function getName(): string
    {
        return match ($this) {
            self::REQUEST => 'Request',
            self::RESPONSE => 'Response',
            self::ERROR_RESPONSE => 'Error Response'
        };
    }
}
