<?php

namespace Tourze\Workerman\RFC3489\Message;

/**
 * STUN协议常量定义
 * 
 * 此类包含RFC3489协议中定义的所有常量值，包括魔术字节、消息类型等
 * 
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-7 消息格式定义
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.1 属性类型定义
 */
final class Constants
{
    /**
     * STUN消息魔术字节（前两位）
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    public const MAGIC_COOKIE = 0x2112A442;
    
    /**
     * STUN消息头部长度（字节）
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    public const HEADER_LENGTH = 20;
    
    /**
     * 消息事务ID长度（字节）
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    public const TRANSACTION_ID_LENGTH = 16;
    
    /**
     * 消息类型位掩码
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    public const MESSAGE_TYPE_MASK = 0x0110;
    
    /**
     * 消息方法位掩码
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    public const MESSAGE_METHOD_MASK = 0x3EEF;
    
    /**
     * 属性对齐长度
     * 属性长度必须是4字节的倍数，不足的需要填充
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1
     */
    public const ATTRIBUTE_ALIGNMENT = 4;
    
    /**
     * TLS传输默认端口
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-8.2
     */
    public const DEFAULT_TLS_PORT = 5349;
    
    /**
     * STUN服务默认端口
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-8.1
     */
    public const DEFAULT_PORT = 3478;
    
    /**
     * RFC3489版本号
     */
    public const RFC_VERSION = '3489';
    
    /**
     * STUN最大消息大小（字节）
     */
    public const MAX_MESSAGE_SIZE = 1500;
    
    /**
     * 用户名最大长度（字节）
     * 
     * 根据RFC3489，用户名不应超过512字节
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.6
     */
    public const MAX_USERNAME_LENGTH = 512;
    
    /**
     * 密码最大长度（字节）
     * 
     * 根据RFC3489，密码不应超过128字节
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.7
     */
    public const MAX_PASSWORD_LENGTH = 128;
    
    /**
     * 错误原因短语最大长度（字节）
     * 
     * 错误原因短语的最大长度限制为763字节 (767 - 4字节头部)
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    public const MAX_ERROR_REASON_LENGTH = 763;
    
    /**
     * 消息完整性HMAC-SHA1哈希长度（字节）
     * 
     * HMAC-SHA1哈希的长度为20字节
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.8
     */
    public const MESSAGE_INTEGRITY_LENGTH = 20;
}
