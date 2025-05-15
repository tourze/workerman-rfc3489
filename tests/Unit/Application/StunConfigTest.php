<?php

namespace Tourze\Workerman\RFC3489\Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Application\StunConfig;
use Tourze\Workerman\RFC3489\Message\Constants;

class StunConfigTest extends TestCase
{
    /**
     * 测试默认配置
     */
    public function testDefaultConfig()
    {
        $config = new StunConfig();
        
        // 验证默认值
        $this->assertEquals('0.0.0.0', $config->getBindAddress());
        $this->assertEquals(Constants::DEFAULT_PORT, $config->getBindPort());
        $this->assertEquals(5000, $config->getRequestTimeout());
        $this->assertEquals([], $config->getServerAddresses());
        $this->assertFalse($config->isDebugMode());
    }
    
    /**
     * 测试设置和获取绑定地址
     */
    public function testBindAddress()
    {
        $config = new StunConfig();
        
        // 设置绑定地址
        $result = $config->setBindAddress('192.168.1.1');
        
        // 验证链式调用返回自身
        $this->assertSame($config, $result);
        
        // 验证值已设置
        $this->assertEquals('192.168.1.1', $config->getBindAddress());
    }
    
    /**
     * 测试设置和获取绑定端口
     */
    public function testBindPort()
    {
        $config = new StunConfig();
        
        // 设置绑定端口
        $result = $config->setBindPort(3479);
        
        // 验证链式调用返回自身
        $this->assertSame($config, $result);
        
        // 验证值已设置
        $this->assertEquals(3479, $config->getBindPort());
    }
    
    /**
     * 测试设置和获取请求超时
     */
    public function testRequestTimeout()
    {
        $config = new StunConfig();
        
        // 设置请求超时
        $result = $config->setRequestTimeout(10000);
        
        // 验证链式调用返回自身
        $this->assertSame($config, $result);
        
        // 验证值已设置
        $this->assertEquals(10000, $config->getRequestTimeout());
    }
    
    /**
     * 测试添加和获取服务器地址
     */
    public function testServerAddresses()
    {
        $config = new StunConfig();
        
        // 添加服务器地址
        $result = $config->addServerAddress('stun.example.com', 3478);
        
        // 验证链式调用返回自身
        $this->assertSame($config, $result);
        
        // 验证值已添加
        $serverAddresses = $config->getServerAddresses();
        $this->assertCount(1, $serverAddresses);
        $this->assertEquals(['stun.example.com', 3478], $serverAddresses[0]);
        
        // 添加第二个服务器地址
        $config->addServerAddress('stun2.example.com', 3479);
        
        // 验证两个地址都存在
        $serverAddresses = $config->getServerAddresses();
        $this->assertCount(2, $serverAddresses);
        $this->assertEquals(['stun.example.com', 3478], $serverAddresses[0]);
        $this->assertEquals(['stun2.example.com', 3479], $serverAddresses[1]);
    }
    
    /**
     * 测试设置和获取调试模式
     */
    public function testDebugMode()
    {
        $config = new StunConfig();
        
        // 默认应为false
        $this->assertFalse($config->isDebugMode());
        
        // 设置为true
        $result = $config->setDebugMode(true);
        
        // 验证链式调用返回自身
        $this->assertSame($config, $result);
        
        // 验证值已设置
        $this->assertTrue($config->isDebugMode());
    }
    
    /**
     * 测试从数组创建配置
     */
    public function testFromArray()
    {
        $configArray = [
            'bindAddress' => '192.168.1.1',
            'bindPort' => 3479,
            'requestTimeout' => 10000,
            'serverAddresses' => [
                ['stun.example.com', 3478],
                ['stun2.example.com', 3479]
            ],
            'debugMode' => true
        ];
        
        $config = StunConfig::fromArray($configArray);
        
        // 验证所有值都正确设置
        $this->assertEquals('192.168.1.1', $config->getBindAddress());
        $this->assertEquals(3479, $config->getBindPort());
        $this->assertEquals(10000, $config->getRequestTimeout());
        $this->assertTrue($config->isDebugMode());
        
        $serverAddresses = $config->getServerAddresses();
        $this->assertCount(2, $serverAddresses);
        $this->assertEquals(['stun.example.com', 3478], $serverAddresses[0]);
        $this->assertEquals(['stun2.example.com', 3479], $serverAddresses[1]);
    }
    
    /**
     * 测试转换为数组
     */
    public function testToArray()
    {
        $config = new StunConfig();
        $config->setBindAddress('192.168.1.1')
               ->setBindPort(3479)
               ->setRequestTimeout(10000)
               ->addServerAddress('stun.example.com', 3478)
               ->addServerAddress('stun2.example.com', 3479)
               ->setDebugMode(true);
               
        $configArray = $config->toArray();
        
        // 验证数组包含所有配置项
        $this->assertEquals('192.168.1.1', $configArray['bindAddress']);
        $this->assertEquals(3479, $configArray['bindPort']);
        $this->assertEquals(10000, $configArray['requestTimeout']);
        $this->assertTrue($configArray['debugMode']);
        
        $this->assertCount(2, $configArray['serverAddresses']);
        $this->assertEquals(['stun.example.com', 3478], $configArray['serverAddresses'][0]);
        $this->assertEquals(['stun2.example.com', 3479], $configArray['serverAddresses'][1]);
    }
    
    /**
     * 测试从YAML创建配置
     */
    public function testFromYaml()
    {
        // 跳过测试，如果Symfony YAML组件不可用
        if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped('Symfony YAML组件不可用');
            return;
        }
        
        $yamlString = <<<YAML
bindAddress: 192.168.1.1
bindPort: 3479
requestTimeout: 10000
serverAddresses:
  - [stun.example.com, 3478]
  - [stun2.example.com, 3479]
debugMode: true
YAML;
        
        $config = StunConfig::fromYaml($yamlString);
        
        // 验证所有值都正确设置
        $this->assertEquals('192.168.1.1', $config->getBindAddress());
        $this->assertEquals(3479, $config->getBindPort());
        $this->assertEquals(10000, $config->getRequestTimeout());
        $this->assertTrue($config->isDebugMode());
        
        $serverAddresses = $config->getServerAddresses();
        $this->assertCount(2, $serverAddresses);
        $this->assertEquals(['stun.example.com', 3478], $serverAddresses[0]);
        $this->assertEquals(['stun2.example.com', 3479], $serverAddresses[1]);
    }
    
    /**
     * 测试转换为YAML
     */
    public function testToYaml()
    {
        // 跳过测试，如果Symfony YAML组件不可用
        if (!class_exists('\Symfony\Component\Yaml\Yaml')) {
            $this->markTestSkipped('Symfony YAML组件不可用');
            return;
        }
        
        $config = new StunConfig();
        $config->setBindAddress('192.168.1.1')
               ->setBindPort(3479)
               ->setRequestTimeout(10000)
               ->addServerAddress('stun.example.com', 3478)
               ->addServerAddress('stun2.example.com', 3479)
               ->setDebugMode(true);
               
        $yamlString = $config->toYaml();
        
        // 验证YAML字符串包含所有配置项
        $this->assertStringContainsString('bindAddress: 192.168.1.1', $yamlString);
        $this->assertStringContainsString('bindPort: 3479', $yamlString);
        $this->assertStringContainsString('requestTimeout: 10000', $yamlString);
        $this->assertStringContainsString('debugMode: true', $yamlString);
        $this->assertStringContainsString('stun.example.com', $yamlString);
        $this->assertStringContainsString('stun2.example.com', $yamlString);
    }
}
