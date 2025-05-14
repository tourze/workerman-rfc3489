<?php

namespace Tourze\Workerman\RFC3489\Message;

/**
 * STUN错误代码枚举
 * 
 * 定义了RFC3489中的STUN错误代码
 * 
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9 错误代码定义
 */
enum ErrorCode: int
{
    /**
     * 400 Bad Request
     * 
     * 请求格式错误或缺少必需属性
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case BAD_REQUEST = 400;
    
    /**
     * 401 Unauthorized
     * 
     * 缺少必需的MESSAGE-INTEGRITY属性
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case UNAUTHORIZED = 401;
    
    /**
     * 420 Unknown Attribute
     * 
     * 请求包含服务器不理解的属性
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case UNKNOWN_ATTRIBUTE = 420;
    
    /**
     * 430 Stale Credentials
     * 
     * MESSAGE-INTEGRITY校验失败，可能是凭证过期
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case STALE_CREDENTIALS = 430;
    
    /**
     * 431 Integrity Check Failure
     * 
     * MESSAGE-INTEGRITY校验失败
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case INTEGRITY_CHECK_FAILURE = 431;
    
    /**
     * 432 Missing Username
     * 
     * 提供了MESSAGE-INTEGRITY属性但缺少USERNAME属性
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case MISSING_USERNAME = 432;
    
    /**
     * 433 Use TLS
     * 
     * 需要使用TLS加密
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case USE_TLS = 433;
    
    /**
     * 500 Server Error
     * 
     * 服务器内部错误
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case SERVER_ERROR = 500;
    
    /**
     * 600 Global Failure
     * 
     * 全局故障
     * 
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case GLOBAL_FAILURE = 600;
    
    /**
     * 获取错误代码的默认原因短语
     * 
     * @return string 原因短语
     */
    public function getDefaultReason(): string
    {
        return match ($this) {
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::UNKNOWN_ATTRIBUTE => 'Unknown Attribute',
            self::STALE_CREDENTIALS => 'Stale Credentials',
            self::INTEGRITY_CHECK_FAILURE => 'Integrity Check Failure',
            self::MISSING_USERNAME => 'Missing Username',
            self::USE_TLS => 'Use TLS',
            self::SERVER_ERROR => 'Server Error',
            self::GLOBAL_FAILURE => 'Global Failure'
        };
    }
    
    /**
     * 从整数值获取错误代码枚举
     * 
     * @param int $code 错误代码值
     * @return self|null 错误代码枚举或null
     */
    public static function fromValue(int $code): ?self
    {
        return self::tryFrom($code);
    }
    
    /**
     * 检查错误代码是否是有效的STUN错误代码
     * 
     * @param int $code 错误代码
     * @return bool 是否是有效的错误代码
     */
    public static function isValid(int $code): bool
    {
        return self::tryFrom($code) !== null;
    }
}
