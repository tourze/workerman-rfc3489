<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Message;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\MessageFormatException;
use Tourze\Workerman\RFC3489\Message\Attributes\MappedAddress;
use Tourze\Workerman\RFC3489\Message\Attributes\Username;
use Tourze\Workerman\RFC3489\Message\AttributeType;
use Tourze\Workerman\RFC3489\Message\Constants;
use Tourze\Workerman\RFC3489\Message\MessageClass;
use Tourze\Workerman\RFC3489\Message\MessageMethod;
use Tourze\Workerman\RFC3489\Message\StunMessage;
use Tourze\Workerman\RFC3489\Utils\BinaryUtils;

class StunMessageTest extends TestCase
{
    public function testConstructor()
    {
        $messageType = Constants::BINDING_REQUEST;
        $message = new StunMessage($messageType);

        $this->assertSame($messageType, $message->getMessageType());
        $this->assertSame(16, strlen($message->getTransactionId()));
    }

    public function testConstructorWithTransactionId()
    {
        $messageType = Constants::BINDING_REQUEST;
        $transactionId = str_repeat("\xAB", 16);
        $message = new StunMessage($messageType, $transactionId);

        $this->assertSame($messageType, $message->getMessageType());
        $this->assertSame($transactionId, $message->getTransactionId());
    }

    public function testGetSetMessageType()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);

        $this->assertSame(Constants::BINDING_REQUEST, $message->getMessageType());

        $message->setMessageType(Constants::BINDING_RESPONSE);
        $this->assertSame(Constants::BINDING_RESPONSE, $message->getMessageType());
    }

    public function testGetMethod()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $this->assertSame(MessageMethod::BINDING, $message->getMethod());

        $message = new StunMessage(Constants::SHARED_SECRET_REQUEST);
        $this->assertSame(MessageMethod::SHARED_SECRET, $message->getMethod());
    }

    public function testGetClass()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $this->assertSame(MessageClass::REQUEST, $message->getClass());

        $message = new StunMessage(Constants::BINDING_RESPONSE);
        $this->assertSame(MessageClass::RESPONSE, $message->getClass());

        $message = new StunMessage(Constants::BINDING_ERROR_RESPONSE);
        $this->assertSame(MessageClass::ERROR_RESPONSE, $message->getClass());
    }

    public function testGetMessageLength()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 没有属性时长度为0
        $this->assertSame(0, $message->getMessageLength());

        // 添加一个属性
        $username = new Username('testuser');
        $message->addAttribute($username);

        // 长度应该是属性的头部(4字节) + 值的长度(8字节) + 可能的填充
        $expectedLength = 4 + 8;
        $this->assertSame($expectedLength, $message->getMessageLength());

        // 添加第二个属性
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);
        $message->addAttribute($mappedAddress);

        // 长度应该增加第二个属性的大小
        $expectedLength += 12; // 包含地址属性的头部(4字节) + IP地址结构(8字节)
        $this->assertSame($expectedLength, $message->getMessageLength());
    }

    public function testGetSetTransactionId()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $originalId = $message->getTransactionId();

        $this->assertSame(16, strlen($originalId));

        $newId = str_repeat("\xCD", 16);
        $message->setTransactionId($newId);

        $this->assertSame($newId, $message->getTransactionId());
    }

    public function testAddGetAttributes()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 初始应该没有属性
        $this->assertEmpty($message->getAttributes());

        // 添加属性
        $username = new Username('testuser');
        $message->addAttribute($username);

        $attributes = $message->getAttributes();
        $this->assertCount(1, $attributes);
        $this->assertArrayHasKey(0, $attributes);
        $firstAttribute = $attributes[0] ?? null;
        $this->assertSame($username, $firstAttribute);

        // 添加第二个属性
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);
        $message->addAttribute($mappedAddress);

        $attributes = $message->getAttributes();
        $this->assertCount(2, $attributes);
        $this->assertArrayHasKey(0, $attributes);
        $this->assertArrayHasKey(1, $attributes);
        $firstAttribute = $attributes[0] ?? null;
        $secondAttribute = $attributes[1] ?? null;
        $this->assertSame($username, $firstAttribute);
        $this->assertSame($mappedAddress, $secondAttribute);
    }

    public function testGetAttributeByType()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 添加属性
        $username = new Username('testuser');
        $message->addAttribute($username);

        // 获取属性
        $retrievedAttr = $message->getAttribute(AttributeType::USERNAME);
        $this->assertSame($username, $retrievedAttr);

        // 尝试获取不存在的属性
        $nonExistentAttr = $message->getAttribute(AttributeType::PASSWORD);
        $this->assertNull($nonExistentAttr);
    }

    public function testGetAttributesByType()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 添加多个同类型属性
        $username1 = new Username('testuser1');
        $username2 = new Username('testuser2');
        $mappedAddress = new MappedAddress('192.168.1.1', 8080);

        $message->addAttribute($username1);
        $message->addAttribute($username2);
        $message->addAttribute($mappedAddress);

        // 获取所有用户名属性
        $usernameAttrs = $message->getAttributesByType(AttributeType::USERNAME);
        $this->assertCount(2, $usernameAttrs);
        $this->assertContains($username1, $usernameAttrs);
        $this->assertContains($username2, $usernameAttrs);

        // 获取所有映射地址属性
        $mappedAddressAttrs = $message->getAttributesByType(AttributeType::MAPPED_ADDRESS);
        $this->assertCount(1, $mappedAddressAttrs);
        $this->assertContains($mappedAddress, $mappedAddressAttrs);

        // 获取不存在的属性类型
        $nonExistentAttrs = $message->getAttributesByType(AttributeType::PASSWORD);
        $this->assertEmpty($nonExistentAttrs);
    }

    public function testHasAttribute()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);

        // 初始应该没有任何属性
        $this->assertFalse($message->hasAttribute(AttributeType::USERNAME));

        // 添加属性
        $username = new Username('testuser');
        $message->addAttribute($username);

        // 现在应该能找到该属性
        $this->assertTrue($message->hasAttribute(AttributeType::USERNAME));

        // 但其他类型的属性应该找不到
        $this->assertFalse($message->hasAttribute(AttributeType::PASSWORD));
    }

    public function testDecode()
    {
        // 创建一个符合协议的二进制消息
        $messageType = Constants::BINDING_REQUEST;
        $messageLength = 8; // 包含一个8字节的用户名属性
        $transactionId = str_repeat("\xAB", 16);

        $header = BinaryUtils::encodeUint16($messageType);
        $header .= BinaryUtils::encodeUint16($messageLength);
        $header .= $transactionId;

        // 添加用户名属性
        $attributeType = AttributeType::USERNAME->value;
        $attributeLength = 4; // 4字节的用户名
        $attributeValue = 'test';

        $attribute = BinaryUtils::encodeUint16($attributeType);
        $attribute .= BinaryUtils::encodeUint16($attributeLength);
        $attribute .= $attributeValue;

        $data = $header . $attribute;

        // 解码消息
        $message = StunMessage::decode($data);

        // 验证解码结果
        $this->assertSame($messageType, $message->getMessageType());
        $this->assertSame($transactionId, $message->getTransactionId());
        $this->assertTrue($message->hasAttribute(AttributeType::USERNAME));

        // 验证属性
        $decodedUsername = $message->getAttribute(AttributeType::USERNAME);
        $this->assertNotNull($decodedUsername);
        $this->assertSame('test', $decodedUsername->getValue());
    }

    public function testDecode_InvalidData()
    {
        // 测试数据太短
        $this->expectException(MessageFormatException::class);
        StunMessage::decode('too short');
    }

    public function testEncode()
    {
        $messageType = Constants::BINDING_REQUEST;
        $transactionId = str_repeat("\xAB", 16);
        $message = new StunMessage($messageType, $transactionId);

        // 添加用户名属性
        $username = new Username('test');
        $message->addAttribute($username);

        // 编码消息
        $encoded = $message->encode();

        // 简单检查编码结构
        $this->assertGreaterThan(20, strlen($encoded)); // 至少包含头部

        // 检查消息类型
        $encodedType = unpack('n', substr($encoded, 0, 2))[1];
        $this->assertEquals($messageType, $encodedType);

        // 检查事务ID
        $encodedTransactionId = substr($encoded, 4, 16);
        $this->assertEquals($transactionId, $encodedTransactionId);

        // 属性编码应该包括用户名属性
        $this->assertStringContainsString('test', $encoded);
    }

    public function testEncode_Empty()
    {
        $messageType = Constants::BINDING_REQUEST;
        $transactionId = str_repeat("\xAB", 16);
        $message = new StunMessage($messageType, $transactionId);

        // 编码消息（没有属性）
        $encoded = $message->encode();

        // 验证编码结果
        $this->assertSame(BinaryUtils::encodeUint16($messageType), substr($encoded, 0, 2));
        $this->assertSame(BinaryUtils::encodeUint16(0), substr($encoded, 2, 2)); // 0字节属性
        $this->assertSame($transactionId, substr($encoded, 4, 16));
        $this->assertSame(20, strlen($encoded)); // 只有头部，没有属性
    }

    public function testToString()
    {
        $message = new StunMessage(Constants::BINDING_REQUEST);
        $message->addAttribute(new Username('testuser'));

        $string = (string)$message;

        // 检查字符串表示是否包含关键信息
        $this->assertStringContainsString('BINDING', $string);
        $this->assertStringContainsString('REQUEST', $string);
        $this->assertStringContainsString('USERNAME', $string);
        $this->assertStringContainsString('testuser', $string);
    }
}
