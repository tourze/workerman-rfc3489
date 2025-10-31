<?php

namespace Tourze\Workerman\RFC3489\Protocol\NatDetection;

use Tourze\Workerman\RFC3489\Exception\TransportException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangeRequest;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\MessageFactory;

/**
 * STUN测试执行器
 *
 * 负责执行NAT类型检测所需的各种测试
 */
class StunTestExecutor
{
    /**
     * 构造函数
     *
     * @param StunRequestSender $requestSender STUN消息发送器
     */
    public function __construct(private readonly StunRequestSender $requestSender)
    {
    }

    /**
     * 记录消息
     */
    private function log(string $level, string $message): void
    {
        $this->requestSender->log($level, $message);
    }

    /**
     * 执行测试I：向服务器发送基本的绑定请求
     *
     * @return array{0: MappedAddress, 1: ChangedAddress}|null 如果成功则返回 [MappedAddress, ChangedAddress]，否则返回null
     */
    public function performTest1(string $serverAddress, int $serverPort): ?array
    {
        // 创建绑定请求
        $request = MessageFactory::createBindingRequest();

        // 发送请求并等待响应
        $response = $this->requestSender->sendRequest($request, $serverAddress, $serverPort);

        if (null === $response) {
            return null;
        }

        // 获取MAPPED-ADDRESS属性
        $mappedAddressAttr = $response->getAttribute(AttributeType::MAPPED_ADDRESS);
        if (null === $mappedAddressAttr || !$mappedAddressAttr instanceof MappedAddress) {
            $this->log('warning', '响应中缺少MAPPED-ADDRESS属性');

            return null;
        }

        // 获取CHANGED-ADDRESS属性
        $changedAddressAttr = $response->getAttribute(AttributeType::CHANGED_ADDRESS);
        if (null === $changedAddressAttr || !$changedAddressAttr instanceof ChangedAddress) {
            $this->log('warning', '响应中缺少CHANGED-ADDRESS属性');

            return null;
        }

        return [$mappedAddressAttr, $changedAddressAttr];
    }

    /**
     * 执行测试I，添加CHANGE-REQUEST属性
     *
     * @param bool $changeIp   是否改变IP
     * @param bool $changePort 是否改变端口
     *
     * @return MappedAddress|null 如果成功则返回MappedAddress，否则返回null
     */
    public function performTest1WithChangeRequest(
        string $serverAddress,
        int $serverPort,
        bool $changeIp,
        bool $changePort,
    ): ?MappedAddress {
        // 创建绑定请求，附带Change Request属性
        $request = MessageFactory::createBindingRequest();
        $changeRequest = new ChangeRequest($changeIp, $changePort);
        $request->addAttribute($changeRequest);

        // 构建日志信息
        $changeDesc = [];
        if ($changeIp) {
            $changeDesc[] = 'IP';
        }
        if ($changePort) {
            $changeDesc[] = '端口';
        }
        $changeDescStr = count($changeDesc) > 0 ? implode('和', $changeDesc) : '无';

        $this->log('info', "测试I(改变{$changeDescStr}): 向初始服务器发送绑定请求，要求改变{$changeDescStr}");

        // 发送请求到初始服务器
        $response = $this->requestSender->sendRequest($request, $serverAddress, $serverPort);

        if (null === $response) {
            $this->log('warning', "测试I(改变{$changeDescStr})失败: 未收到响应");

            return null;
        }

        // 获取映射地址属性
        $mappedAddress = $response->getAttribute(AttributeType::MAPPED_ADDRESS);
        if (null === $mappedAddress) {
            $this->log('warning', "测试I(改变{$changeDescStr})失败: 响应中没有映射地址");

            return null;
        }

        if (!$mappedAddress instanceof MappedAddress) {
            $this->log('warning', "测试I(改变{$changeDescStr})失败: 映射地址属性类型错误");

            return null;
        }

        $this->log('info', "测试I(改变{$changeDescStr})成功，新映射地址: {$mappedAddress->getIp()}:{$mappedAddress->getPort()}");

        return $mappedAddress;
    }

    /**
     * 执行测试II：向变更地址发送绑定请求
     *
     * @param ChangedAddress $changedAddress        变更地址
     * @param string         $originalServerAddress 原始服务器地址（用于处理0.0.0.0情况）
     *
     * @return bool 如果成功收到响应则返回true
     */
    public function performTest2(ChangedAddress $changedAddress, string $originalServerAddress): bool
    {
        $changedIp = $changedAddress->getIp();
        $changedPort = $changedAddress->getPort();

        // 修复: 如果变更地址IP为0.0.0.0，则使用原始服务器IP，只改变端口
        if ('0.0.0.0' === $changedIp) {
            $this->log('warning', '服务器返回的变更IP无效(0.0.0.0)，将使用原始服务器IP');
            $changedIp = $originalServerAddress;
        }

        $this->log('info', '测试II: 向变更地址发送绑定请求');

        // 创建绑定请求
        $request = MessageFactory::createBindingRequest();

        // 发送请求到变更地址
        $response = $this->requestSender->sendRequest($request, $changedIp, $changedPort);

        if (null === $response) {
            $this->log('warning', '测试II失败: 未收到响应');

            return false;
        }

        // 获取映射地址属性
        $mappedAddress = $response->getAttribute(AttributeType::MAPPED_ADDRESS);
        if (null === $mappedAddress) {
            $this->log('warning', '测试II失败: 响应中没有映射地址');

            return false;
        }

        return true;
    }

    /**
     * 执行测试III：向初始服务器发送绑定请求，要求改变端口
     *
     * @return bool 如果成功收到响应则返回true
     */
    public function performTest3(string $serverAddress, int $serverPort): bool
    {
        // 创建绑定请求
        $request = MessageFactory::createBindingRequest();

        // 添加CHANGE-REQUEST属性，只改变端口
        $changeRequest = new ChangeRequest(false, true);
        $request->addAttribute($changeRequest);

        try {
            // 发送请求并等待响应
            $response = $this->requestSender->sendRequest($request, $serverAddress, $serverPort);

            return null !== $response;
        } catch (TransportException $e) {
            $this->log('warning', '测试III失败: ' . $e->getMessage());

            return false;
        }
    }
}
