<?php

namespace Tourze\Workerman\RFC3489\Protocol\Server\Handler;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Tourze\Workerman\RFC3489\Exception\StunException;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\ChangeRequest;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\ReflectedFrom;
use Tourze\Workerman\RFC3489\Message\Attributes\SourceAddress;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageFactory;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Transport\StunTransport;

/**
 * STUN Binding 请求处理器
 */
final class BindingRequestHandler implements StunMessageHandlerInterface
{
    /**
     * STUN传输层
     */
    private StunTransport $transport;

    /**
     * 备用IP地址
     */
    private string $alternateIp;

    /**
     * 备用端口
     */
    private int $alternatePort;

    /**
     * 日志记录器
     */
    private ?LoggerInterface $logger;

    /**
     * 认证处理器
     *
     * @var callable|null
     */
    private $authHandler;

    /**
     * 创建一个Binding请求处理器
     *
     * @param StunTransport        $transport     传输层实例
     * @param string               $alternateIp   备用IP地址
     * @param int                  $alternatePort 备用端口
     * @param callable|null        $authHandler   认证处理器
     * @param LoggerInterface|null $logger        日志记录器
     */
    public function __construct(
        StunTransport $transport,
        string $alternateIp,
        int $alternatePort,
        ?callable $authHandler = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->transport = $transport;
        $this->alternateIp = $alternateIp;
        $this->alternatePort = $alternatePort;
        $this->authHandler = $authHandler;
        $this->logger = $logger;
    }

    /**
     * 处理Binding请求
     *
     * @param StunMessage $request    请求消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     *
     * @return StunMessage|null 响应消息
     */
    public function handleMessage(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        $this->logInfo("收到来自 {$clientIp}:{$clientPort} 的Binding请求");

        try {
            $validationResult = $this->validateRequest($request, $clientIp, $clientPort);
            if (null !== $validationResult) {
                return $validationResult;
            }

            $response = $this->createSuccessResponse($request, $clientIp, $clientPort);
            $this->processSpecialRequests($request, $response, $clientIp, $clientPort);

            $this->logInfo("发送Binding响应到 {$clientIp}:{$clientPort}");

            return $response;
        } catch (StunException $e) {
            $this->logError('处理Binding请求时发生错误: ' . $e->getMessage());

            return MessageFactory::createErrorResponse(
                $request,
                ErrorCode::SERVER_ERROR,
                $e->getMessage()
            );
        } catch (\Throwable $e) {
            $this->logError('处理Binding请求时发生未知错误: ' . $e->getMessage());

            return MessageFactory::createErrorResponse(
                $request,
                ErrorCode::SERVER_ERROR,
                '服务器内部错误'
            );
        }
    }

    /**
     * 验证请求
     *
     * @param StunMessage $request    请求消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     *
     * @return StunMessage|null 如果验证失败返回错误响应，否则返回null
     */
    private function validateRequest(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        $unknownAttributes = $this->checkRequiredAttributes($request);
        if ([] !== $unknownAttributes) {
            $this->logWarning('请求包含未知属性: ' . implode(', ', $unknownAttributes));

            return MessageFactory::createUnknownAttributesResponse($request, $unknownAttributes);
        }

        return $this->validateAuthentication($request, $clientIp, $clientPort);
    }

    /**
     * 验证认证
     *
     * @param StunMessage $request    请求消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     *
     * @return StunMessage|null 如果认证失败返回错误响应，否则返回null
     */
    private function validateAuthentication(StunMessage $request, string $clientIp, int $clientPort): ?StunMessage
    {
        if (null === $this->authHandler) {
            return null;
        }

        $authResult = call_user_func($this->authHandler, $request, $clientIp, $clientPort);
        if (true !== $authResult) {
            $this->logWarning("认证失败: {$authResult}");

            return MessageFactory::createErrorResponse(
                $request,
                ErrorCode::UNAUTHORIZED,
                $authResult
            );
        }

        return null;
    }

    /**
     * 创建成功响应
     *
     * @param StunMessage $request    请求消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     *
     * @return StunMessage 成功响应
     */
    private function createSuccessResponse(StunMessage $request, string $clientIp, int $clientPort): StunMessage
    {
        $response = MessageFactory::createSuccessResponse($request);

        $this->addMappedAddress($response, $clientIp, $clientPort);
        $this->addSourceAddress($response);
        $this->addChangedAddress($response);

        return $response;
    }

    /**
     * 添加映射地址属性
     *
     * @param StunMessage $response   响应消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     */
    private function addMappedAddress(StunMessage $response, string $clientIp, int $clientPort): void
    {
        $mappedAddress = new MappedAddress($clientIp, $clientPort);
        $response->addAttribute($mappedAddress);
    }

    /**
     * 添加源地址属性
     *
     * @param StunMessage $response 响应消息
     */
    private function addSourceAddress(StunMessage $response): void
    {
        $localAddr = $this->transport->getLocalAddress();
        if (null !== $localAddr) {
            $sourceAddress = new SourceAddress($localAddr[0], $localAddr[1]);
            $response->addAttribute($sourceAddress);
        }
    }

    /**
     * 添加变更地址属性
     *
     * @param StunMessage $response 响应消息
     */
    private function addChangedAddress(StunMessage $response): void
    {
        $changedAddress = new ChangedAddress($this->alternateIp, $this->alternatePort);
        $response->addAttribute($changedAddress);
    }

    /**
     * 处理特殊请求
     *
     * @param StunMessage $request    请求消息
     * @param StunMessage $response   响应消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     */
    private function processSpecialRequests(StunMessage $request, StunMessage $response, string $clientIp, int $clientPort): void
    {
        $this->processChangeRequest($request);
        $this->processResponseAddress($request, $response, $clientIp, $clientPort);
    }

    /**
     * 处理改变请求
     *
     * @param StunMessage $request 请求消息
     */
    private function processChangeRequest(StunMessage $request): void
    {
        $changeRequest = $request->getAttribute(AttributeType::CHANGE_REQUEST);
        if (null !== $changeRequest && $changeRequest instanceof ChangeRequest) {
            $changeIp = $changeRequest->isChangeIp();
            $changePort = $changeRequest->isChangePort();

            $this->logInfo('客户端请求改变IP: ' . ($changeIp ? '是' : '否') . ', 改变端口: ' . ($changePort ? '是' : '否'));

            // TODO: 如果请求改变IP和/或端口，应该从备用地址发送响应
            // 这需要实现一个备用传输层或在当前传输层上实现多地址绑定
        }
    }

    /**
     * 处理响应地址
     *
     * @param StunMessage $request    请求消息
     * @param StunMessage $response   响应消息
     * @param string      $clientIp   客户端IP地址
     * @param int         $clientPort 客户端端口
     */
    private function processResponseAddress(StunMessage $request, StunMessage $response, string $clientIp, int $clientPort): void
    {
        $responseAddress = $request->getAttribute(AttributeType::RESPONSE_ADDRESS);
        if (null !== $responseAddress) {
            $reflectedFrom = new ReflectedFrom($clientIp, $clientPort);
            $response->addAttribute($reflectedFrom);

            // TODO: 如果指定了响应地址，应该将响应发送到该地址
            // 这需要实现一个将响应发送到指定地址的机制
        }
    }

    /**
     * 检查请求中的必需属性
     *
     * @param StunMessage $request 请求消息
     *
     * @return array<int> 未知属性列表
     */
    private function checkRequiredAttributes(StunMessage $request): array
    {
        $unknownAttributes = [];

        foreach ($request->getAttributes() as $attribute) {
            try {
                AttributeType::tryFrom($attribute->getType());
            } catch (\ValueError $e) {
                $unknownAttributes[] = $attribute->getType();
            }
        }

        return $unknownAttributes;
    }

    /**
     * 日志记录 - 信息级别
     *
     * @param string $message 日志消息
     */
    private function logInfo(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::INFO, "[BindingHandler] {$message}");
        }
    }

    /**
     * 日志记录 - 警告级别
     *
     * @param string $message 日志消息
     */
    private function logWarning(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::WARNING, "[BindingHandler] {$message}");
        }
    }

    /**
     * 日志记录 - 错误级别
     *
     * @param string $message 日志消息
     */
    private function logError(string $message): void
    {
        if (null !== $this->logger) {
            $this->logger->log(LogLevel::ERROR, "[BindingHandler] {$message}");
        }
    }
}
