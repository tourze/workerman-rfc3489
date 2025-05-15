<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Message\Constants;

class ConstantsTest extends TestCase
{
    public function testMagicCookie()
    {
        $this->assertSame(0x2112A442, Constants::MAGIC_COOKIE);
    }
    
    public function testMessageHeaderLength()
    {
        $this->assertSame(20, Constants::MESSAGE_HEADER_LENGTH);
    }
    
    public function testAttributeHeaderLength()
    {
        $this->assertSame(4, Constants::ATTRIBUTE_HEADER_LENGTH);
    }
    
    public function testMessageTypeLength()
    {
        $this->assertSame(2, Constants::MESSAGE_TYPE_LENGTH);
    }
    
    public function testMessageLengthLength()
    {
        $this->assertSame(2, Constants::MESSAGE_LENGTH_LENGTH);
    }
    
    public function testTransactionIdLength()
    {
        $this->assertSame(16, Constants::TRANSACTION_ID_LENGTH);
    }
    
    public function testMessageTypeMask()
    {
        $this->assertSame(0x0110, Constants::MESSAGE_TYPE_MASK);
    }
    
    public function testMethodMask()
    {
        $this->assertSame(0x3EEF, Constants::METHOD_MASK);
    }
    
    public function testClassMask()
    {
        $this->assertSame(0xC110, Constants::CLASS_MASK);
    }
    
    public function testClassRequestMask()
    {
        $this->assertSame(0x0000, Constants::CLASS_REQUEST_MASK);
    }
    
    public function testClassSuccessResponseMask()
    {
        $this->assertSame(0x0100, Constants::CLASS_SUCCESS_RESPONSE_MASK);
    }
    
    public function testClassErrorResponseMask()
    {
        $this->assertSame(0x0110, Constants::CLASS_ERROR_RESPONSE_MASK);
    }
    
    public function testMethodBindingMask()
    {
        $this->assertSame(0x0001, Constants::METHOD_BINDING_MASK);
    }
    
    public function testMethodSharedSecretMask()
    {
        $this->assertSame(0x0002, Constants::METHOD_SHARED_SECRET_MASK);
    }
    
    public function testBindingRequest()
    {
        $this->assertSame(0x0001, Constants::BINDING_REQUEST);
    }
    
    public function testBindingResponse()
    {
        $this->assertSame(0x0101, Constants::BINDING_RESPONSE);
    }
    
    public function testBindingErrorResponse()
    {
        $this->assertSame(0x0111, Constants::BINDING_ERROR_RESPONSE);
    }
    
    public function testSharedSecretRequest()
    {
        $this->assertSame(0x0002, Constants::SHARED_SECRET_REQUEST);
    }
    
    public function testSharedSecretResponse()
    {
        $this->assertSame(0x0102, Constants::SHARED_SECRET_RESPONSE);
    }
    
    public function testSharedSecretErrorResponse()
    {
        $this->assertSame(0x0112, Constants::SHARED_SECRET_ERROR_RESPONSE);
    }
}
