<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message\Attributes;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\ErrorCodeAttribute;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\ErrorCode;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * ErrorCodeAttribute 测试类
 */
class ErrorCodeAttributeTest extends TestCase
{
    public function testInheritance()
    {
        $attribute = new ErrorCodeAttribute(400);
        
        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }
    
    public function testAttributeType()
    {
        $attribute = new ErrorCodeAttribute(400);
        
        $this->assertSame(AttributeType::ERROR_CODE->value, $attribute->getType());
    }
    
    public function testConstructorWithIntCode()
    {
        $code = 400;
        $reason = 'Bad Request';
        $attribute = new ErrorCodeAttribute($code, $reason);
        
        $this->assertSame($code, $attribute->getCode());
        $this->assertSame($reason, $attribute->getReason());
    }
    
    public function testConstructorWithErrorCodeEnum()
    {
        $errorCode = ErrorCode::BAD_REQUEST;
        $attribute = new ErrorCodeAttribute($errorCode);
        
        $this->assertSame($errorCode->value, $attribute->getCode());
        $this->assertSame($errorCode->getDefaultReason(), $attribute->getReason());
    }
    
    public function testConstructorWithCustomReason()
    {
        $errorCode = ErrorCode::BAD_REQUEST;
        $customReason = 'Custom reason';
        $attribute = new ErrorCodeAttribute($errorCode, $customReason);
        
        $this->assertSame($errorCode->value, $attribute->getCode());
        $this->assertSame($customReason, $attribute->getReason());
    }
    
    public function testConstructorWithUnknownCode()
    {
        $code = 999;
        $attribute = new ErrorCodeAttribute($code);
        
        $this->assertSame($code, $attribute->getCode());
        $this->assertSame('Unknown Error', $attribute->getReason());
    }
    
    public function testSetCodeWithInt()
    {
        $attribute = new ErrorCodeAttribute(400);
        $newCode = 500;
        $newReason = 'Server Error';
        
        $result = $attribute->setCode($newCode, $newReason);
        
        $this->assertSame($attribute, $result);
        $this->assertSame($newCode, $attribute->getCode());
        $this->assertSame($newReason, $attribute->getReason());
    }
    
    public function testSetCodeWithEnum()
    {
        $attribute = new ErrorCodeAttribute(400);
        $errorCode = ErrorCode::UNAUTHORIZED;
        
        $result = $attribute->setCode($errorCode);
        
        $this->assertSame($attribute, $result);
        $this->assertSame($errorCode->value, $attribute->getCode());
        $this->assertSame($errorCode->getDefaultReason(), $attribute->getReason());
    }
    
    public function testSetReason()
    {
        $attribute = new ErrorCodeAttribute(400);
        $newReason = 'New reason';
        
        $result = $attribute->setReason($newReason);
        
        $this->assertSame($attribute, $result);
        $this->assertSame($newReason, $attribute->getReason());
    }
    
    public function testGetLength()
    {
        $reason = 'Bad Request';
        $attribute = new ErrorCodeAttribute(400, $reason);
        
        $expectedLength = 4 + strlen($reason);
        $this->assertSame($expectedLength, $attribute->getLength());
    }
    
    public function testEncode()
    {
        $attribute = new ErrorCodeAttribute(400, 'Bad Request');
        $encoded = $attribute->encode();
        

        $this->assertNotEmpty($encoded);
        $this->assertGreaterThanOrEqual(4, strlen($encoded));
    }
    
    public function testEncodeDecode()
    {
        $originalCode = 400;
        $originalReason = 'Bad Request';
        $attribute = new ErrorCodeAttribute($originalCode, $originalReason);
        
        $this->assertSame($originalCode, $attribute->getCode());
        $this->assertSame($originalReason, $attribute->getReason());
    }
    
    public function testDecodeInvalidLength()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ERROR-CODE属性长度不足');
        
        $data = "\x00\x00"; // 长度不足
        ErrorCodeAttribute::decode($data, 0, 2);
    }
    
    public function testToString()
    {
        $code = 400;
        $reason = 'Bad Request';
        $attribute = new ErrorCodeAttribute($code, $reason);
        
        $expectedString = "ERROR-CODE: $code $reason";
        $this->assertSame($expectedString, (string) $attribute);
    }
}