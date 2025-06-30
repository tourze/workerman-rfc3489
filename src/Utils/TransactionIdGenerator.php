<?php

namespace Tourze\Workerman\RFC3489\Utils;

use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;

/**
 * STUN事务ID生成器
 *
 * 负责生成和管理STUN事务ID
 *
 * @see https://datatracker.ietf.org/doc/html/rfc3489#section-10.1 事务ID定义
 */
class TransactionIdGenerator
{
    /**
     * 已使用的事务ID集合
     *
     * @var array
     */
    private static array $usedIds = [];

    /**
     * 生成一个新的随机事务ID
     *
     * @param int|bool $length 事务ID长度（字节数）或是否确保唯一性
     * @param bool $unique 是否确保唯一性
     * @return string 随机事务ID
     * @throws InvalidArgumentException 如果长度参数无效
     */
    public static function generate(int|bool $length = Constants::TRANSACTION_ID_LENGTH, bool $unique = true): string
    {
        // 兼容旧的调用方式，布尔值表示unique
        if (is_bool($length)) {
            $unique = $length;
            $length = Constants::TRANSACTION_ID_LENGTH;
        }
        
        // 检查长度的有效性
        if ($length <= 0) {
            throw new InvalidArgumentException('事务ID长度必须大于0');
        }
        
        $transactionId = random_bytes($length);

        if ($unique) {
            $key = bin2hex($transactionId);

            // 如果已存在此ID，则重新生成
            while (isset(self::$usedIds[$key])) {
                $transactionId = random_bytes($length);
                $key = bin2hex($transactionId);
            }

            // 记录已使用的ID
            self::$usedIds[$key] = true;
        }

        return $transactionId;
    }

    /**
     * 释放一个事务ID，允许重用
     *
     * @param string $transactionId 事务ID
     * @return void
     */
    public static function release(string $transactionId): void
    {
        $key = bin2hex($transactionId);
        unset(self::$usedIds[$key]);
    }

    /**
     * 检查事务ID是否已被使用
     *
     * @param string $transactionId 事务ID
     * @return bool 是否已被使用
     */
    public static function isUsed(string $transactionId): bool
    {
        $key = bin2hex($transactionId);
        return isset(self::$usedIds[$key]);
    }

    /**
     * 清除所有已使用的事务ID记录
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$usedIds = [];
    }

    /**
     * 获取已使用的事务ID数量
     *
     * @return int 已使用的事务ID数量
     */
    public static function count(): int
    {
        return count(self::$usedIds);
    }
}
