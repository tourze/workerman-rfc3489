<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server\Handler;

use Tourze\Workerman\RFC3489\Message\StunMessage;

/**
 * STUN消息处理器接口
 */
interface StunMessageHandlerInterface
{
    /**
     * 处理STUN消息
     *
     * @param StunMessage $request    请求消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     *
     * @return StunMessage|null 响应消息，或null表示不需要响应
     */
    public function handleMessage(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage;
}
