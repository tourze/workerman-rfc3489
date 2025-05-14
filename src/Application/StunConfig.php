<?php

namespace Tourze\Workerman\RFC3489\Application;

use Tourze\Workerman\RFC3489\Message\Constants;

/**
 * STUN应用配置类
 *
 * 提供STUN应用的配置选项
 */
class StunConfig
{
    /**
     * 默认配置
     */
    private const DEFAULT_CONFIG = [
        // 服务器配置
        'server' => [
            'bind_ip' => '0.0.0.0',
            'bind_port' => Constants::DEFAULT_PORT,
            'alternate_ip' => '0.0.0.0',
            'alternate_port' => 0,
            'workers' => 1,
            'daemon' => false,
        ],
        
        // 客户端配置
        'client' => [
            'server_address' => 'stun.l.google.com',
            'server_port' => 19302,
            'timeout' => 5000,
            'retry_count' => 3,
        ],
        
        // 传输配置
        'transport' => [
            'type' => 'udp',
            'local_ip' => '0.0.0.0',
            'local_port' => 0,
            'socket_timeout' => 30,
            'socket_buffer_size' => 65535,
        ],
        
        // 日志配置
        'log' => [
            'enabled' => true,
            'level' => 'info', // debug, info, warning, error
            'file' => null,
        ],
    ];
    
    /**
     * 当前配置
     */
    private array $config;
    
    /**
     * 创建一个新的STUN配置
     *
     * @param array $config 配置数组，将与默认配置合并
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace_recursive(self::DEFAULT_CONFIG, $config);
    }
    
    /**
     * 获取配置值
     *
     * @param string $path 配置路径，使用点号分隔，例如 'server.bind_ip'
     * @param mixed $default 默认值，如果配置不存在则返回此值
     * @return mixed 配置值
     */
    public function get(string $path, $default = null)
    {
        $keys = explode('.', $path);
        $value = $this->config;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }
        
        return $value;
    }
    
    /**
     * 设置配置值
     *
     * @param string $path 配置路径，使用点号分隔，例如 'server.bind_ip'
     * @param mixed $value 配置值
     * @return self 当前实例，用于链式调用
     */
    public function set(string $path, $value): self
    {
        $keys = explode('.', $path);
        $lastKey = array_pop($keys);
        $config = &$this->config;
        
        foreach ($keys as $key) {
            if (!isset($config[$key]) || !is_array($config[$key])) {
                $config[$key] = [];
            }
            $config = &$config[$key];
        }
        
        $config[$lastKey] = $value;
        
        return $this;
    }
    
    /**
     * 检查配置是否存在
     *
     * @param string $path 配置路径
     * @return bool 如果配置存在则返回true
     */
    public function has(string $path): bool
    {
        $keys = explode('.', $path);
        $value = $this->config;
        
        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return false;
            }
            $value = $value[$key];
        }
        
        return true;
    }
    
    /**
     * 获取整个配置数组
     *
     * @return array 配置数组
     */
    public function toArray(): array
    {
        return $this->config;
    }
    
    /**
     * 从文件加载配置
     *
     * @param string $file 配置文件路径
     * @return self 当前实例，用于链式调用
     * @throws \RuntimeException 如果无法加载配置文件
     */
    public function loadFromFile(string $file): self
    {
        if (!file_exists($file)) {
            throw new \RuntimeException("配置文件不存在: $file");
        }
        
        $config = null;
        
        // 根据文件扩展名决定如何加载
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'php':
                $config = require $file;
                break;
                
            case 'json':
                $content = file_get_contents($file);
                $config = json_decode($content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \RuntimeException("无法解析JSON配置文件: " . json_last_error_msg());
                }
                break;
                
            case 'yaml':
            case 'yml':
                if (!function_exists('yaml_parse_file')) {
                    throw new \RuntimeException("无法解析YAML配置文件: 未安装yaml扩展");
                }
                $config = yaml_parse_file($file);
                if ($config === false) {
                    throw new \RuntimeException("无法解析YAML配置文件");
                }
                break;
                
            default:
                throw new \RuntimeException("不支持的配置文件格式: $extension");
        }
        
        if (!is_array($config)) {
            throw new \RuntimeException("配置文件必须返回数组");
        }
        
        $this->config = array_replace_recursive(self::DEFAULT_CONFIG, $this->config, $config);
        
        return $this;
    }
    
    /**
     * 保存配置到文件
     *
     * @param string $file 文件路径
     * @return bool 如果保存成功则返回true
     * @throws \RuntimeException 如果无法保存配置文件
     */
    public function saveToFile(string $file): bool
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        
        switch ($extension) {
            case 'php':
                $content = "<?php\n\nreturn " . var_export($this->config, true) . ";\n";
                break;
                
            case 'json':
                $content = json_encode($this->config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
                
            case 'yaml':
            case 'yml':
                if (!function_exists('yaml_emit')) {
                    throw new \RuntimeException("无法生成YAML配置文件: 未安装yaml扩展");
                }
                $content = yaml_emit($this->config);
                break;
                
            default:
                throw new \RuntimeException("不支持的配置文件格式: $extension");
        }
        
        $result = file_put_contents($file, $content);
        
        return $result !== false;
    }
} 