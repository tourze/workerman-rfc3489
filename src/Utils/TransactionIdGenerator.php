<?php

namespace Tourze\Workerman\RFC3489\Utils;

use Tourze\Workerman\RFC3489\Message\Constants;

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
     * @param bool $unique 是否确保唯一性
     * @return string 16字节的随机事务ID
     */
    public static function generate(bool $unique = true): string
    {
        $transactionId = random_bytes(Constants::TRANSACTION_ID_LENGTH);

        if ($unique) {
            $key = bin2hex($transactionId);

            // 如果已存在此ID，则重新生成
            while (isset(self::$usedIds[$key])) {
                $transactionId = random_bytes(Constants::TRANSACTION_ID_LENGTH);
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
