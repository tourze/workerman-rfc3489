<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\Workerman\RFC3489\Protocol\NatType;

/**
 * NatType 测试类
 *
 * @internal
 */
#[CoversClass(NatType::class)]
final class NatTypeTest extends AbstractEnumTestCase
{
    public function testGetDescription(): void
    {
        $this->assertStringContainsString('NAT类型', NatType::UNKNOWN->getDescription());
        $this->assertStringContainsString('互联网', NatType::OPEN_INTERNET->getDescription());
        $this->assertStringContainsString('锥形', NatType::FULL_CONE->getDescription());
    }

    public function testIsSupportP2P(): void
    {
        $this->assertTrue(NatType::OPEN_INTERNET->isSupportP2P());
        $this->assertTrue(NatType::FULL_CONE->isSupportP2P());
        $this->assertTrue(NatType::RESTRICTED_CONE->isSupportP2P());
        $this->assertTrue(NatType::PORT_RESTRICTED_CONE->isSupportP2P());

        $this->assertFalse(NatType::SYMMETRIC->isSupportP2P());
        $this->assertFalse(NatType::SYMMETRIC_UDP_FIREWALL->isSupportP2P());
        $this->assertFalse(NatType::BLOCKED->isSupportP2P());
        $this->assertFalse(NatType::UNKNOWN->isSupportP2P());
    }

    public function testGetP2PAdvice(): void
    {
        $this->assertStringContainsString('STUN', NatType::RESTRICTED_CONE->getP2PAdvice());
        $this->assertStringContainsString('TURN', NatType::SYMMETRIC->getP2PAdvice());
        $this->assertStringContainsString('直接', NatType::OPEN_INTERNET->getP2PAdvice());
    }

    public function testToArray(): void
    {
        $array = NatType::OPEN_INTERNET->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame('Open Internet', $array['value']);
        $this->assertSame('Open Internet', $array['label']);

        // 验证数组结构完整性
        $this->assertCount(2, $array);

        // 测试其他NAT类型
        $fullConeArray = NatType::FULL_CONE->toArray();
        $this->assertSame('Full Cone NAT', $fullConeArray['value']);
        $this->assertSame('Full Cone NAT', $fullConeArray['label']);
    }
}
