<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message\Attributes;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Message\Attributes\Password;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageAttribute;

/**
 * Password 测试类
 */
class PasswordTest extends TestCase
{
    public function testInheritance()
    {
        $attribute = new Password('test');
        
        $this->assertInstanceOf(MessageAttribute::class, $attribute);
    }
    
    public function testAttributeType()
    {
        $attribute = new Password('test');
        
        $this->assertSame(AttributeType::PASSWORD->value, $attribute->getType());
    }
    
    public function testConstructor()
    {
        $password = 'test_password';
        $attribute = new Password($password);
        
        $this->assertSame($password, $attribute->getPassword());
    }
    
    public function testSetPassword()
    {
        $attribute = new Password('old');
        $newPassword = 'new_password';
        
        $result = $attribute->setPassword($newPassword);
        
        $this->assertSame($attribute, $result);
        $this->assertSame($newPassword, $attribute->getPassword());
    }
    
    public function testGetValue()
    {
        $password = 'test_password';
        $attribute = new Password($password);
        
        $this->assertSame($password, $attribute->getValue());
    }
    
    public function testSetValueWithString()
    {
        $attribute = new Password('old');
        $newPassword = 'new_password';
        
        $result = $attribute->setValue($newPassword);
        
        $this->assertSame($attribute, $result);
        $this->assertSame($newPassword, $attribute->getPassword());
    }
    
    public function testSetValueWithNonString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Password属性值必须是字符串');
        
        $attribute = new Password('test');
        $attribute->setValue(123);
    }
    
    public function testGetLength()
    {
        $password = 'test';
        $attribute = new Password($password);
        
        $this->assertSame(strlen($password), $attribute->getLength());
    }
    
    public function testGetLengthWithLongPassword()
    {
        $longPassword = str_repeat('a', Constants::MAX_PASSWORD_LENGTH + 10);
        $attribute = new Password($longPassword);
        
        $this->assertSame(Constants::MAX_PASSWORD_LENGTH, $attribute->getLength());
    }
    
    public function testEncode()
    {
        $password = 'test';
        $attribute = new Password($password);
        $encoded = $attribute->encode();
        

        $this->assertNotEmpty($encoded);
        $this->assertStringContainsString($password, $encoded);
    }
    
    public function testDecode()
    {
        $password = 'test_password';
        $attribute = new Password($password);
        $encoded = $attribute->encode();
        
        $decoded = Password::decode($encoded, 0, strlen($encoded));
        
        $this->assertSame($password, $decoded->getPassword());
    }
    
    public function testDecodeInvalidType()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('无法解析PASSWORD属性');
        
        $data = pack('nn', 0x9999, 4) . 'test'; // 错误的类型
        Password::decode($data, 0, 8);
    }
    
    public function testToString()
    {
        $password = 'test_password';
        $attribute = new Password($password);
        
        $string = (string) $attribute;
        
        $this->assertStringContainsString('PASSWORD', $string);
        $this->assertStringContainsString('(masked)', $string);
        $this->assertStringNotContainsString($password, $string); // 密码应该被遮蔽
    }
    
    public function testToStringWithShortPassword()
    {
        $password = 'test';
        $attribute = new Password($password);
        
        $string = (string) $attribute;
        
        $this->assertStringContainsString('****', $string);
    }
    
    public function testToStringWithLongPassword()
    {
        $password = str_repeat('a', 20);
        $attribute = new Password($password);
        
        $string = (string) $attribute;
        
        $this->assertStringContainsString('********', $string); // 只显示8个*
    }
}