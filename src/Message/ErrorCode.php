<?php

namespace Tourze\Workerman\RFC3489\Message;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * STUN错误代码枚举
 *
 * 定义了RFC3489中的STUN错误代码
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9 错误代码定义
 */
enum ErrorCode: int implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

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
     * 获取错误代码的原因短语
     *
     * @return string 原因短语
     */
    public function getReason(): string
    {
        return $this->getDefaultReason();
    }

    /**
     * 获取错误代码的标签（实现Labelable接口）
     *
     * @return string 错误代码标签
     */
    public function getLabel(): string
    {
        return $this->getReason();
    }

    /**
     * 获取错误代码的类别（百位数字）
     *
     * @return int 错误类别
     */
    public function getClass(): int
    {
        return (int)($this->value / 100);
    }

    /**
     * 获取错误代码的编号（个位和十位数字）
     *
     * @return int 错误编号
     */
    public function getNumber(): int
    {
        return $this->value % 100;
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

    /**
     * 将枚举转换为字符串
     *
     * @return string 枚举的字符串表示
     */
    public function toString(): string
    {
        return sprintf('%s (%d): %s', $this->name, $this->value, $this->getReason());
    }
}
