<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol\Server\Handler;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\BindingRequestHandler;
use Tourze\Workerman\RFC3489\Protocol\Server\Handler\StunMessageHandlerInterface;

/**
 * BindingRequestHandler 测试类
 */
class BindingRequestHandlerTest extends TestCase
{
    public function testClassExists()
    {
        $this->assertTrue(class_exists(\Tourze\Workerman\RFC3489\Protocol\Server\Handler\BindingRequestHandler::class));
    }
}