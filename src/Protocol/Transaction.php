<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Tourze\Workerman\RFC3489\Exception\InvalidStunMessageException;
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
     * 当前重试次数
     */
    private int $currentRetry = 0;

    /**
     * 事务状态
     */
    private TransactionState $state;

    /**
     * 事务回调函数
     *
     * @var callable|null
     */
    private $callback;

    /**
     * 创建一个新的事务
     *
     * @param StunMessage $request         请求消息
     * @param string      $destinationIp   目标IP地址
     * @param int         $destinationPort 目标端口
     * @param int         $timeout         超时时间（毫秒）
     * @param int         $retryCount      重试次数
     */
    public function __construct(
        private readonly StunMessage $request,
        private readonly string $destinationIp,
        private readonly int $destinationPort,
        private readonly int $timeout = 5000,
        private readonly int $retryCount = 3,
    ) {
        $this->transactionId = $request->getTransactionId();
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
     *
     * @throws ProtocolException 如果事务已完成或响应事务ID不匹配
     */
    public function setResponse(StunMessage $response): void
    {
        // 检查事务状态
        if (TransactionState::COMPLETED === $this->state) {
            throw ProtocolException::invalidState($this->state->name, TransactionState::PENDING->name);
        }

        // 检查事务ID是否匹配
        if ($response->getTransactionId() !== $this->transactionId) {
            throw ProtocolException::invalidTransaction($response->getTransactionId());
        }

        $this->response = $response;
        $this->completionTime = microtime(true);
        $this->state = TransactionState::COMPLETED;

        // 如果有回调，则调用回调
        if (null !== $this->callback) {
            call_user_func($this->callback, $this);
        }
    }

    /**
     * 检查事务是否已超时
     *
     * @return bool 如果事务已超时则返回true
     */
    public function isTimedOut(): bool
    {
        if (TransactionState::COMPLETED === $this->state) {
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
        return TransactionState::COMPLETED === $this->state;
    }

    /**
     * 获取事务耗时（毫秒）
     *
     * @return float|null 事务耗时，如果尚未完成则为null
     */
    public function getDuration(): ?float
    {
        if (null === $this->completionTime) {
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
     */
    public function setCallback(callable $callback): void
    {
        $this->callback = $callback;
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
        ++$this->currentRetry;

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
     *
     * @return StunMessage 响应消息
     *
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

        // 事务完成时，响应必须已设置
        if (null === $this->response) {
            throw new InvalidStunMessageException('事务已完成但响应为空');
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
