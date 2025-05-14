<?php

namespace Tourze\Workerman\RFC3489\Protocol;

/**
 * STUN事务状态枚举
 */
enum TransactionState
{
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
} 