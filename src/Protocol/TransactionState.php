<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * STUN事务状态枚举
 */
enum TransactionState implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    /**
     * 待处理状态
     */
    case PENDING;

    /**
     * 已完成状态
     */
    case COMPLETED;

    /**
     * 超时状态
     */
    case TIMEOUT;

    /**
     * 失败状态
     */
    case FAILED;

    /**
     * 取消状态
     */
    case CANCELLED;

    /**
     * 获取事务状态的标签（实现Labelable接口）
     *
     * @return string 事务状态标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::COMPLETED => '已完成',
            self::TIMEOUT => '超时',
            self::FAILED => '失败',
            self::CANCELLED => '取消',
        };
    }
}
