<?php

namespace Tourze\Workerman\RFC3489\Message;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * STUN消息属性类型枚举
 *
 * 定义了RFC3489中的STUN消息属性类型
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2 属性定义
 */
enum AttributeType: int implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    /**
     * MAPPED-ADDRESS属性
     *
     * 包含STUN服务器看到的客户端的IP地址和端口（即NAT映射后的地址）
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.1
     */
    case MAPPED_ADDRESS = 0x0001;

    /**
     * RESPONSE-ADDRESS属性
     *
     * 指定响应应该发送到的地址
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.2
     */
    case RESPONSE_ADDRESS = 0x0002;

    /**
     * CHANGE-REQUEST属性
     *
     * 请求服务器在不同的IP和/或端口发送响应
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.3
     */
    case CHANGE_REQUEST = 0x0003;

    /**
     * SOURCE-ADDRESS属性
     *
     * 包含发送响应的STUN服务器的源IP地址和端口
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.4
     */
    case SOURCE_ADDRESS = 0x0004;

    /**
     * CHANGED-ADDRESS属性
     *
     * 包含服务器在更改IP/端口后会使用的IP地址和端口
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.5
     */
    case CHANGED_ADDRESS = 0x0005;

    /**
     * USERNAME属性
     *
     * 用于消息完整性验证的用户名
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.6
     */
    case USERNAME = 0x0006;

    /**
     * PASSWORD属性
     *
     * 用于Shared Secret响应中返回密码
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.7
     */
    case PASSWORD = 0x0007;

    /**
     * MESSAGE-INTEGRITY属性
     *
     * 包含HMAC-SHA1消息完整性校验值
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.8
     */
    case MESSAGE_INTEGRITY = 0x0008;

    /**
     * ERROR-CODE属性
     *
     * 表示错误响应中的错误代码和原因短语
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
     */
    case ERROR_CODE = 0x0009;

    /**
     * UNKNOWN-ATTRIBUTES属性
     *
     * 在错误响应中列出服务器不理解的属性
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.10
     */
    case UNKNOWN_ATTRIBUTES = 0x000A;

    /**
     * REFLECTED-FROM属性
     *
     * 指示响应来源的客户端的IP地址
     *
     * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.11
     */
    case REFLECTED_FROM = 0x000B;

    /**
     * 获取属性类型的可读名称
     *
     * @return string 属性类型名称
     */
    public function getName(): string
    {
        return match ($this) {
            self::MAPPED_ADDRESS => 'MAPPED-ADDRESS',
            self::RESPONSE_ADDRESS => 'RESPONSE-ADDRESS',
            self::CHANGE_REQUEST => 'CHANGE-REQUEST',
            self::SOURCE_ADDRESS => 'SOURCE-ADDRESS',
            self::CHANGED_ADDRESS => 'CHANGED-ADDRESS',
            self::USERNAME => 'USERNAME',
            self::PASSWORD => 'PASSWORD',
            self::MESSAGE_INTEGRITY => 'MESSAGE-INTEGRITY',
            self::ERROR_CODE => 'ERROR-CODE',
            self::UNKNOWN_ATTRIBUTES => 'UNKNOWN-ATTRIBUTES',
            self::REFLECTED_FROM => 'REFLECTED-FROM',
        };
    }

    /**
     * 获取属性类型的标签（实现Labelable接口）
     *
     * @return string 属性类型标签
     */
    public function getLabel(): string
    {
        return $this->getName();
    }

    /**
     * 从整数值获取属性类型枚举
     *
     * @param int $type 属性类型值
     *
     * @return self|null 属性类型枚举或null
     */
    public static function fromValue(int $type): ?self
    {
        return self::tryFrom($type);
    }

    /**
     * 检查属性类型是否是已知类型
     *
     * @param int $type 属性类型值
     *
     * @return bool 是否是已知类型
     */
    public static function isKnown(int $type): bool
    {
        return null !== self::tryFrom($type);
    }

    /**
     * 检查属性类型是否是已知类型
     *
     * @param int $type 属性类型值
     *
     * @return bool 是否是已知类型
     */
    public static function isKnownAttribute(int $type): bool
    {
        return null !== self::tryFrom($type);
    }

    /**
     * 获取所有地址类型属性
     *
     * @return array<self> 地址类型属性数组
     */
    public static function addressAttributes(): array
    {
        return [
            self::MAPPED_ADDRESS,
            self::RESPONSE_ADDRESS,
            self::SOURCE_ADDRESS,
            self::CHANGED_ADDRESS,
            self::REFLECTED_FROM,
        ];
    }

    /**
     * 检查属性类型是否是地址类型
     *
     * @param int $type 属性类型值
     *
     * @return bool 是否是地址类型
     */
    public static function isAddressAttribute(int $type): bool
    {
        $attribute = self::tryFrom($type);
        if (null === $attribute) {
            return false;
        }

        return in_array($attribute, self::addressAttributes(), true);
    }

    /**
     * 将枚举转换为字符串
     *
     * @return string 枚举名称
     */
    public function toString(): string
    {
        return $this->name;
    }
}
