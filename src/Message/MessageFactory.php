<?php

namespace Tourze\Workerman\RFC3489\Message;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\ErrorCodeAttribute;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\MessageIntegrity;
use Tourze\Workerman\RFC3489\Message\Attributes\Password;
use Tourze\Workerman\RFC3489\Message\Attributes\ReflectedFrom;
use Tourze\Workerman\RFC3489\Message\Attributes\SourceAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\UnknownAttributes;
use Tourze\Workerman\RFC3489\Message\Attributes\Username;
use Tourze\Workerman\RFC3489\Utils\TransactionIdGenerator;

/**
 * STUN消息工厂类
 *
 * 提供便捷方法创建各种类型的STUN消息
 */
class MessageFactory
{
    /**
     * 创建Binding请求消息
     *
     * @param string|null $transactionId 事务ID，默认自动生成
     * @return StunMessage 创建的消息实例
     */
    public static function createBindingRequest(?string $transactionId = null): StunMessage
    {
        $messageType = self::createMessageType(MessageMethod::BINDING, MessageClass::REQUEST);
        $transactionId = $transactionId ?? TransactionIdGenerator::generate();

        return new StunMessage($messageType, $transactionId);
    }

    /**
     * 创建Binding成功响应消息
     *
     * @param string $transactionId 事务ID，必须与请求一致
     * @param string $mappedIp 映射的IP地址
     * @param int $mappedPort 映射的端口
     * @return StunMessage 创建的消息实例
     */
    public static function createBindingResponse(string $transactionId, string $mappedIp, int $mappedPort): StunMessage
    {
        $messageType = self::createMessageType(MessageMethod::BINDING, MessageClass::RESPONSE);
        $message = new StunMessage($messageType, $transactionId);

        // 添加映射地址属性
        $mappedAddress = new MappedAddress($mappedIp, $mappedPort);
        $message->addAttribute($mappedAddress);

        return $message;
    }

    /**
     * 创建Binding错误响应消息
     *
     * @param string $transactionId 事务ID，必须与请求一致
     * @param int|ErrorCode $errorCode 错误代码
     * @param string|null $reason 错误原因，为null时使用默认原因
     * @return StunMessage 创建的消息实例
     */
    public static function createBindingErrorResponse(
        string        $transactionId,
        int|ErrorCode $errorCode,
        ?string       $reason = null
    ): StunMessage {
        $messageType = self::createMessageType(MessageMethod::BINDING, MessageClass::ERROR_RESPONSE);
        $message = new StunMessage($messageType, $transactionId);

        // 添加错误代码属性
        $errorAttr = new ErrorCodeAttribute($errorCode, $reason);
        $message->addAttribute($errorAttr);

        return $message;
    }

    /**
     * 创建Shared Secret请求消息
     *
     * @param string|null $transactionId 事务ID，默认自动生成
     * @return StunMessage 创建的消息实例
     */
    public static function createSharedSecretRequest(?string $transactionId = null): StunMessage
    {
        $messageType = self::createMessageType(MessageMethod::SHARED_SECRET, MessageClass::REQUEST);
        $transactionId = $transactionId ?? TransactionIdGenerator::generate();

        return new StunMessage($messageType, $transactionId);
    }

    /**
     * 创建Shared Secret成功响应消息
     *
     * @param string $transactionId 事务ID，必须与请求一致
     * @param string $username 用户名
     * @param string $password 密码
     * @return StunMessage 创建的消息实例
     */
    public static function createSharedSecretResponse(
        string $transactionId,
        string $username,
        string $password
    ): StunMessage {
        $messageType = self::createMessageType(MessageMethod::SHARED_SECRET, MessageClass::RESPONSE);
        $message = new StunMessage($messageType, $transactionId);

        // 添加用户名和密码属性
        $usernameAttr = new Username($username);
        $passwordAttr = new Password($password);

        $message->addAttribute($usernameAttr);
        $message->addAttribute($passwordAttr);

        return $message;
    }

    /**
     * 创建Shared Secret错误响应消息
     *
     * @param string $transactionId 事务ID，必须与请求一致
     * @param int|ErrorCode $errorCode 错误代码
     * @param string|null $reason 错误原因，为null时使用默认原因
     * @return StunMessage 创建的消息实例
     */
    public static function createSharedSecretErrorResponse(
        string $transactionId,
        int|ErrorCode $errorCode,
        ?string $reason = null
    ): StunMessage {
        $messageType = self::createMessageType(MessageMethod::SHARED_SECRET, MessageClass::ERROR_RESPONSE);
        $message = new StunMessage($messageType, $transactionId);

        // 添加错误代码属性
        $errorAttr = new ErrorCodeAttribute($errorCode, $reason);
        $message->addAttribute($errorAttr);

        return $message;
    }

    /**
     * 基于请求消息创建成功响应消息
     *
     * @param StunMessage $request 请求消息
     * @return StunMessage 响应消息
     */
    public static function createSuccessResponse(StunMessage $request): StunMessage
    {
        $method = $request->getMethod();
        if ($method === null) {
            throw new InvalidArgumentException('无效的请求消息类型');
        }

        $messageType = self::createMessageType($method, MessageClass::RESPONSE);

        return new StunMessage($messageType, $request->getTransactionId());
    }

    /**
     * 基于请求消息创建错误响应消息
     *
     * @param StunMessage $request 请求消息
     * @param int|ErrorCode $errorCode 错误代码
     * @param string|null $reason 错误原因，为null时使用默认原因
     * @return StunMessage 错误响应消息
     */
    public static function createErrorResponse(
        StunMessage   $request,
        int|ErrorCode $errorCode,
        ?string       $reason = null
    ): StunMessage {
        $method = $request->getMethod();
        if ($method === null) {
            throw new InvalidArgumentException('无效的请求消息类型');
        }

        $messageType = self::createMessageType($method, MessageClass::ERROR_RESPONSE);
        $message = new StunMessage($messageType, $request->getTransactionId());

        // 添加错误代码属性
        $errorAttr = new ErrorCodeAttribute($errorCode, $reason);
        $message->addAttribute($errorAttr);

        return $message;
    }

    /**
     * 添加未知属性错误响应
     *
     * @param StunMessage $request 请求消息
     * @param array $unknownAttributes 未知属性类型列表
     * @return StunMessage 错误响应消息
     */
    public static function createUnknownAttributesResponse(
        StunMessage $request,
        array       $unknownAttributes
    ): StunMessage {
        $message = self::createErrorResponse($request, ErrorCode::UNKNOWN_ATTRIBUTE);

        // 添加未知属性列表
        $unknownAttrsAttr = new UnknownAttributes($unknownAttributes);
        $message->addAttribute($unknownAttrsAttr);

        return $message;
    }

    /**
     * 添加消息完整性属性
     *
     * @param StunMessage $message 消息实例
     * @param string $key 用于计算完整性的密钥
     * @return StunMessage 添加了完整性属性的消息实例
     */
    public static function addMessageIntegrity(StunMessage $message, string $key): StunMessage
    {
        $integrity = new MessageIntegrity();
        $message->addAttribute($integrity);

        // 先计算消息编码，不包括完整性属性
        $encoded = $message->encode();

        // 计算完整性值并更新属性
        $integrity->calculateAndSetHmac($encoded, $key);

        return $message;
    }

    /**
     * 添加服务器地址信息属性
     *
     * @param StunMessage $message 消息实例
     * @param string $sourceIp 源IP地址
     * @param int $sourcePort 源端口
     * @param string $changedIp 变更后的IP地址
     * @param int $changedPort 变更后的端口
     * @return StunMessage 添加了地址信息的消息实例
     */
    public static function addServerAddresses(
        StunMessage $message,
        string      $sourceIp,
        int         $sourcePort,
        string      $changedIp,
        int         $changedPort
    ): StunMessage {
        $sourceAddr = new SourceAddress($sourceIp, $sourcePort);
        $changedAddr = new ChangedAddress($changedIp, $changedPort);

        $message->addAttribute($sourceAddr);
        $message->addAttribute($changedAddr);

        return $message;
    }

    /**
     * 添加反射来源属性
     *
     * @param StunMessage $message 消息实例
     * @param string $ip 客户端IP地址
     * @param int $port 客户端端口
     * @return StunMessage 添加了反射来源属性的消息实例
     */
    public static function addReflectedFrom(StunMessage $message, string $ip, int $port): StunMessage
    {
        $reflectedFrom = new ReflectedFrom($ip, $port);
        $message->addAttribute($reflectedFrom);

        return $message;
    }

    /**
     * 创建消息类型值
     *
     * @param MessageMethod $method 消息方法
     * @param MessageClass $class 消息类别
     * @return int 消息类型值
     */
    private static function createMessageType(MessageMethod $method, MessageClass $class): int
    {
        return $method->value | $class->value;
    }
}
