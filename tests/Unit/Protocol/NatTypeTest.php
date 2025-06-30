<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Protocol;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\NatType;

/**
 * NatType 测试类
 */
class NatTypeTest extends TestCase
{
    public function testEnumValues()
    {
        $this->assertSame('Unknown', NatType::UNKNOWN->value);
        $this->assertSame('Open Internet', NatType::OPEN_INTERNET->value);
        $this->assertSame('Full Cone NAT', NatType::FULL_CONE->value);
        $this->assertSame('Restricted Cone NAT', NatType::RESTRICTED_CONE->value);
        $this->assertSame('Port Restricted Cone NAT', NatType::PORT_RESTRICTED_CONE->value);
        $this->assertSame('Symmetric NAT', NatType::SYMMETRIC->value);
        $this->assertSame('Symmetric UDP Firewall', NatType::SYMMETRIC_UDP_FIREWALL->value);
        $this->assertSame('Blocked', NatType::BLOCKED->value);
    }
    
    public function testGetDescription()
    {
        $this->assertStringContainsString('NAT类型', NatType::UNKNOWN->getDescription());
        $this->assertStringContainsString('互联网', NatType::OPEN_INTERNET->getDescription());
        $this->assertStringContainsString('锥形', NatType::FULL_CONE->getDescription());
    }
    
    public function testIsSupportP2P()
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
    
    public function testGetP2PAdvice()
    {
        $this->assertStringContainsString('STUN', NatType::RESTRICTED_CONE->getP2PAdvice());
        $this->assertStringContainsString('TURN', NatType::SYMMETRIC->getP2PAdvice());
        $this->assertStringContainsString('直接', NatType::OPEN_INTERNET->getP2PAdvice());
    }
    
    public function testGetLabel()
    {
        $this->assertSame('Unknown', NatType::UNKNOWN->getLabel());
        $this->assertSame('Open Internet', NatType::OPEN_INTERNET->getLabel());
    }
}