<?php

namespace Tourze\Workerman\RFC3489\Message\Attributes;

use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * ERROR-CODE属性
 *
 * 表示错误响应中的错误代码和原因短语
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-11.2.9
 */
class ErrorCodeAttribute extends MessageAttribute
{
    /**
     * 错误代码
     */
    private int $code;

    /**
     * 原因短语
     */
    private string $reason;

    /**
     * 创建一个新的ERROR-CODE属性
     *
     * @param int|ErrorCode $code   错误代码或ErrorCode枚举
     * @param string|null   $reason 原因短语，为null时使用默认原因短语
     */
    public function __construct(int|ErrorCode $code, ?string $reason = null)
    {
        parent::__construct(AttributeType::ERROR_CODE);

        if ($code instanceof ErrorCode) {
            $this->code = $code->value;
            $this->reason = $reason ?? $code->getDefaultReason();
        } else {
            $this->code = $code;
            $errorCodeEnum = ErrorCode::tryFrom($code);
            $this->reason = $reason ?? ($errorCodeEnum?->getDefaultReason() ?? 'Unknown Error');
        }
    }

    /**
     * 获取错误代码
     *
     * @return int 错误代码
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * 获取原因短语
     *
     * @return string 原因短语
     */
    public function getReason(): string
    {
        return $this->reason;
    }

    /**
     * 设置错误代码
     *
     * @param int|ErrorCode $code   错误代码或ErrorCode枚举
     * @param string|null   $reason 原因短语，为null时使用默认原因短语
     */
    public function setCode(int|ErrorCode $code, ?string $reason = null): void
    {
        if ($code instanceof ErrorCode) {
            $this->code = $code->value;
            $this->reason = $reason ?? $code->getDefaultReason();
        } else {
            $this->code = $code;
            $errorCodeEnum = ErrorCode::tryFrom($code);
            $this->reason = $reason ?? ($errorCodeEnum?->getDefaultReason() ?? 'Unknown Error');
        }
    }

    /**
     * 设置原因短语
     *
     * @param string $reason 原因短语
     */
    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function encode(): string
    {
        $class = intdiv($this->code, 100);
        $number = $this->code % 100;

        // 前两个字节为保留字段，必须为0
        // 第三个字节高3位为错误类，低5位为0
        // 第四个字节为错误号
        $header = pack('CCn', 0, 0, ($class << 5) | $number);

        // 原因短语使用UTF-8编码
        $reasonBytes = substr($this->reason, 0, 763); // 最大长度限制为763（767 - 4字节头部）

        return $header . $reasonBytes;
    }

    public static function decode(string $data, int $offset, int $length): static
    {
        if ($length < 4) {
            throw new InvalidArgumentException('ERROR-CODE属性长度不足');
        }

        // 解析错误代码
        $errorClassAndNumber = unpack('x2C1class/C1number', substr($data, $offset, 4));

        if (false === $errorClassAndNumber) {
            throw new InvalidArgumentException('无法解析ERROR-CODE属性');
        }

        // 确保数组键存在，避免Undefined array key警告
        $classValue = $errorClassAndNumber['class'] ?? 0;
        $numberValue = $errorClassAndNumber['number'] ?? 0;

        $class = ($classValue >> 5) & 0x07;
        $number = $numberValue;
        $code = $class * 100 + $number;

        // 解析原因短语
        $reason = '';
        if ($length > 4) {
            $reason = substr($data, $offset + 4, $length - 4);
        }

        // @phpstan-ignore new.static
        return new static($code, $reason);
    }

    public function getLength(): int
    {
        // 4字节头部 + 原因短语长度
        return 4 + strlen($this->reason);
    }

    public function __toString(): string
    {
        return sprintf('ERROR-CODE: %d %s', $this->code, $this->reason);
    }
}
