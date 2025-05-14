<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Tourze\Workerman\RFC3489\Exception\ProtocolException;
use Tourze\Workerman\RFC3489\Exception\TimeoutException;
use Tourze\Workerman\RFC3489\Message\StunMessage;

/**
 * STUN事务处理类
 *
 * 用于跟踪和管理STUN事务，将请求与响应匹配
 */
class Transaction
{
    /**
     * 事务ID
     */
    private string $transactionId;
    
    /**
     * 请求消息
     */
    private StunMessage $request;
    
    /**
     * 响应消息
     */
    private ?StunMessage $response = null;
    
    /**
     * 事务开始时间（微秒）
     */
    private float $startTime;
    
    /**
     * 事务完成时间（微秒）
     */
    private ?float $completionTime = null;
    
    /**
     * 事务超时时间（毫秒）
     */
    private int $timeout;
    
    /**
     * 重试次数
     */
    private int $retryCount;
    
    /**
     * 当前重试次数
     */
    private int $currentRetry = 0;
    
    /**
     * 事务状态
     */
    private TransactionState $state;
    
    /**
     * 目标IP地址
     */
    private string $destinationIp;
    
    /**
     * 目标端口
     */
    private int $destinationPort;
    
    /**
     * 事务回调函数
     * 
     * @var callable|null
     */
    private $callback = null;
    
    /**
     * 创建一个新的事务
     *
     * @param StunMessage $request 请求消息
     * @param string $destinationIp 目标IP地址
     * @param int $destinationPort 目标端口
     * @param int $timeout 超时时间（毫秒）
     * @param int $retryCount 重试次数
     */
    public function __construct(
        StunMessage $request,
        string $destinationIp,
        int $destinationPort,
        int $timeout = 5000,
        int $retryCount = 3
    ) {
        $this->request = $request;
        $this->transactionId = $request->getTransactionId();
        $this->destinationIp = $destinationIp;
        $this->destinationPort = $destinationPort;
        $this->timeout = $timeout;
        $this->retryCount = $retryCount;
        $this->startTime = microtime(true);
        $this->state = TransactionState::PENDING;
    }
    
    /**
     * 获取事务ID
     *
     * @return string 事务ID
     */
    public function getTransactionId(): string
    {
        return $this->transactionId;
    }
    
    /**
     * 获取请求消息
     *
     * @return StunMessage 请求消息
     */
    public function getRequest(): StunMessage
    {
        return $this->request;
    }
    
    /**
     * 获取响应消息
     *
     * @return StunMessage|null 响应消息，如果尚未收到则为null
     */
    public function getResponse(): ?StunMessage
    {
        return $this->response;
    }
    
    /**
     * 设置响应消息
     *
     * @param StunMessage $response 响应消息
     * @return self 当前实例，用于链式调用
     * @throws ProtocolException 如果事务已完成或响应事务ID不匹配
     */
    public function setResponse(StunMessage $response): self
    {
        // 检查事务状态
        if ($this->state === TransactionState::COMPLETED) {
            throw ProtocolException::invalidState(
                $this->state->name,
                TransactionState::PENDING->name
            );
        }
        
        // 检查事务ID是否匹配
        if ($response->getTransactionId() !== $this->transactionId) {
            throw ProtocolException::invalidTransaction($response->getTransactionId());
        }
        
        $this->response = $response;
        $this->completionTime = microtime(true);
        $this->state = TransactionState::COMPLETED;
        
        // 如果有回调，则调用回调
        if ($this->callback !== null) {
            call_user_func($this->callback, $this);
        }
        
        return $this;
    }
    
    /**
     * 检查事务是否已超时
     *
     * @return bool 如果事务已超时则返回true
     */
    public function isTimedOut(): bool
    {
        if ($this->state === TransactionState::COMPLETED) {
            return false;
        }
        
        $elapsedMs = (microtime(true) - $this->startTime) * 1000;
        return $elapsedMs >= $this->timeout;
    }
    
    /**
     * 检查事务是否已完成
     *
     * @return bool 如果事务已完成则返回true
     */
    public function isCompleted(): bool
    {
        return $this->state === TransactionState::COMPLETED;
    }
    
    /**
     * 获取事务耗时（毫秒）
     *
     * @return float|null 事务耗时，如果尚未完成则为null
     */
    public function getDuration(): ?float
    {
        if ($this->completionTime === null) {
            return null;
        }
        
        return ($this->completionTime - $this->startTime) * 1000;
    }
    
    /**
     * 获取目标IP地址
     *
     * @return string 目标IP地址
     */
    public function getDestinationIp(): string
    {
        return $this->destinationIp;
    }
    
    /**
     * 获取目标端口
     *
     * @return int 目标端口
     */
    public function getDestinationPort(): int
    {
        return $this->destinationPort;
    }
    
    /**
     * 获取超时时间
     *
     * @return int 超时时间（毫秒）
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
    
    /**
     * 设置事务回调
     *
     * @param callable $callback 回调函数，接收Transaction实例作为参数
     * @return self 当前实例，用于链式调用
     */
    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }
    
    /**
     * 获取当前重试次数
     *
     * @return int 当前重试次数
     */
    public function getCurrentRetry(): int
    {
        return $this->currentRetry;
    }
    
    /**
     * 增加重试次数
     *
     * @return self 当前实例，用于链式调用
     */
    public function incrementRetry(): self
    {
        $this->currentRetry++;
        return $this;
    }
    
    /**
     * 检查是否可以重试
     *
     * @return bool 如果可以重试则返回true
     */
    public function canRetry(): bool
    {
        return $this->currentRetry < $this->retryCount;
    }
    
    /**
     * 获取事务状态
     *
     * @return TransactionState 事务状态
     */
    public function getState(): TransactionState
    {
        return $this->state;
    }
    
    /**
     * 等待事务完成
     *
     * @param int|null $timeout 等待超时时间（毫秒），为null时使用事务默认超时时间
     * @return StunMessage 响应消息
     * @throws TimeoutException 如果超时
     */
    public function waitForCompletion(?int $timeout = null): StunMessage
    {
        $timeoutMs = $timeout ?? $this->timeout;
        $startTime = microtime(true);
        
        while (!$this->isCompleted()) {
            if ((microtime(true) - $startTime) * 1000 >= $timeoutMs) {
                throw TimeoutException::transactionTimeout($this->transactionId, $timeoutMs);
            }
            
            // 休眠一小段时间，避免CPU占用过高
            usleep(10000); // 10ms
        }
        
        return $this->response;
    }
    
    /**
     * 重置事务
     *
     * @return self 当前实例，用于链式调用
     */
    public function reset(): self
    {
        $this->startTime = microtime(true);
        $this->completionTime = null;
        $this->response = null;
        $this->state = TransactionState::PENDING;
        $this->currentRetry = 0;
        
        return $this;
    }
} 