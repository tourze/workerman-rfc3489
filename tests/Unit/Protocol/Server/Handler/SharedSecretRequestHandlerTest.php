<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\SharedSecretRequestHandler;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;

/**
 * SharedSecretRequestHandler 测试类
 */
class SharedSecretRequestHandlerTest extends TestCase
{
    public function testClassExists()
    {
        $this->assertTrue(class_exists(\Tourze\Workerman\RFC3489\Protocol\Server\Handler\SharedSecretRequestHandler::class));
    }
}