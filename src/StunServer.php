<?php

namespace Tourze\Workerman\STUNServer;

use Workerman\Connection\UdpConnection;
use Workerman\Worker;

/**
 * Class StunServer
 * 实现完整的 STUN 服务器功能，支持 NAT 类型检测、认证等
 */
class StunServer extends Worker
{
    // STUN 消息类型
    const STUN_BINDING_REQUEST = 0x0001;
    const STUN_BINDING_RESPONSE = 0x0101;
    const STUN_BINDING_ERROR_RESPONSE = 0x0111;
    const STUN_SHARED_SECRET_REQUEST = 0x0002;
    const STUN_SHARED_SECRET_RESPONSE = 0x0102;
    // ... 其他消息类型（如 Allocate）

    // Magic Cookie
    const STUN_MAGIC_COOKIE = 0x2112A442;

    // STUN 属性类型
    const STUN_ATTR_MAPPED_ADDRESS = 0x0001;
    const STUN_ATTR_XOR_MAPPED_ADDRESS = 0x0020;
    const STUN_ATTR_CHANGE_REQUEST = 0x0003;
    const STUN_ATTR_SOURCE_ADDRESS = 0x0004;
    const STUN_ATTR_CHANGED_ADDRESS = 0x0005;
    const STUN_ATTR_USERNAME = 0x0006;
    const STUN_ATTR_PASSWORD = 0x0007;
    const STUN_ATTR_MESSAGE_INTEGRITY = 0x0008;
    const STUN_ATTR_ERROR_CODE = 0x0009;
    const STUN_ATTR_UNKNOWN_ATTRIBUTES = 0x000A;
    const STUN_ATTR_REALM = 0x0014;
    const STUN_ATTR_NONCE = 0x0015;
    const STUN_ATTR_XOR_RELAYED_ADDRESS = 0x0021;
    // ... 其他属性类型

    // 共享密钥（示例用途，实际应用中应安全存储）
    private $sharedSecret = 'your_shared_secret';

    // Realm 和 Nonce 存储
    private $realm = 'example.com';
    private $nonce = 'random_nonce';

    // 用户认证信息（示例用途，实际应用中应从数据库或安全存储读取）
    private $users = [
        'user1' => 'password1',
        'user2' => 'password2',
        // 添加更多用户
    ];

    /**
     * StunServer 构造函数
     * @param string $address
     */
    public function __construct($address = 'udp://0.0.0.0:3478')
    {
        parent::__construct($address);

        // 根据 CPU 核心数设置进程数量
        $this->count = max(1, intval(shell_exec('nproc'))); // 自动根据CPU核心数设置

        // 设置消息处理回调
        $this->onMessage = [$this, 'onMessage'];
    }

    /**
     * 处理收到的消息
     * @param UdpConnection $connection
     * @param string $data
     * @param array $client_address
     */
    public function onMessage(UdpConnection $connection, $data, $client_address)
    {
        // 检查数据长度是否至少为 20 字节（STUN 消息头长度）
        if (strlen($data) < 20) {
            Worker::log('Received invalid STUN message (too short).');
            $this->sendErrorResponse($connection, '', 400, 'Bad Request');
            return;
        }

        // 解析 STUN 消息头
        $header = unpack('nType/nLength/NMagicCookie', substr($data, 0, 8));
        $type = $header['Type'];
        $length = $header['Length'];
        $magic_cookie = $header['MagicCookie'];

        // 获取 Transaction ID
        $transaction_id = substr($data, 8, 12);

        // 验证 Magic Cookie
        if ($magic_cookie !== self::STUN_MAGIC_COOKIE) {
            Worker::log('Invalid Magic Cookie.');
            $this->sendErrorResponse($connection, $transaction_id, 400, 'Bad Request');
            return;
        }

        // 检查消息类型是否受支持
        $supportedTypes = [
            self::STUN_BINDING_REQUEST,
            self::STUN_SHARED_SECRET_REQUEST,
            // 添加其他受支持的消息类型
            // self::STUN_ALLOCATE_REQUEST,
        ];

        if (!in_array($type, $supportedTypes)) {
            Worker::log("Unsupported STUN message type: $type");
            $this->sendErrorResponse($connection, $transaction_id, 420, 'Unknown Attribute');
            return;
        }

        // 解析属性
        $attributes = $this->parseAttributes($data, 20, $length);

        // 获取客户端的 IP 和端口，从 $connection 中获取
        $client_ip = $connection->getRemoteIp();
        $client_port = $connection->getRemotePort();

        Worker::log("Received message type: $type from $client_ip:$client_port");

        // 根据消息类型处理
        switch ($type) {
            case self::STUN_BINDING_REQUEST:
                $this->handleBindingRequest($connection, $transaction_id, $attributes, $client_ip, $client_port);
                break;

            case self::STUN_SHARED_SECRET_REQUEST:
                $this->handleSharedSecretRequest($connection, $transaction_id, $attributes, $client_ip, $client_port);
                break;

            // 处理其他消息类型
            // case self::STUN_ALLOCATE_REQUEST:
            //     $this->handleAllocateRequest($connection, $transaction_id, $attributes, $client_ip, $client_port);
            //     break;

            default:
                Worker::log("Unhandled STUN message type: $type");
                $this->sendErrorResponse($connection, $transaction_id, 420, 'Unknown Attribute');
                break;
        }
    }

    /**
     * 处理 Binding Request 消息
     * @param UdpConnection $connection
     * @param string $transaction_id
     * @param array $attributes
     * @param string $client_ip
     * @param int $client_port
     */
    private function handleBindingRequest(UdpConnection $connection, $transaction_id, $attributes, $client_ip, $client_port)
    {
        // 认证验证（MESSAGE-INTEGRITY）
        $isAuthenticated = false;
        if (isset($attributes[self::STUN_ATTR_MESSAGE_INTEGRITY])) {
            $isAuthenticated = $this->authenticate($attributes, $connection);
            if (!$isAuthenticated) {
                Worker::log("Invalid MESSAGE-INTEGRITY from $client_ip:$client_port");
                $this->sendErrorResponse($connection, $transaction_id, 401, 'Unauthorized');
                return;
            }
        } else {
            Worker::log("Missing MESSAGE-INTEGRITY from $client_ip:$client_port");
            $this->sendErrorResponse($connection, $transaction_id, 400, 'Bad Request');
            return;
        }

        // 处理 CHANGE-REQUEST 属性
        $change_ip = false;
        $change_port = false;
        if (isset($attributes[self::STUN_ATTR_CHANGE_REQUEST])) {
            // CHANGE-REQUEST 属性格式：1字节保留 + 1字节标志 + 2字节保留
            $change_request_data = unpack('Cignored/Cflags/nignored2', $attributes[self::STUN_ATTR_CHANGE_REQUEST]['value']);
            $flags = $change_request_data['flags'];
            $change_ip = ($flags & 0x04) !== 0;
            $change_port = ($flags & 0x02) !== 0;
        }

        // 处理 USERNAME 属性（如果有）
        $username = null;
        if (isset($attributes[self::STUN_ATTR_USERNAME])) {
            $username = $attributes[self::STUN_ATTR_USERNAME]['value'];
            Worker::log("Received USERNAME: $username from $client_ip:$client_port");
            // 根据应用需求处理 USERNAME，例如查找对应的密码
            // TODO: 实现根据 USERNAME 查找密码或验证逻辑
            // 例如：
            /*
            if (!isset($this->users[$username]) || $this->users[$username] !== $password) {
                $this->sendErrorResponse($connection, $transaction_id, 401, 'Unauthorized');
                return;
            }
            */
        }

        // 构造 XOR-MAPPED-ADDRESS 属性
        $mapped_address = $this->createXorMappedAddress($client_ip, $client_port, $transaction_id);

        // 构造响应属性
        $response_attributes = $mapped_address;

        // 如果 CHANGE-REQUEST 属性存在，返回 CHANGED-ADDRESS 属性
        if ($change_ip || $change_port) {
            $changed_address = $this->createChangedAddress($client_ip, $client_port, $transaction_id, $change_ip, $change_port);
            $response_attributes .= $changed_address;
        }

        // 构造 REALM 和 NONCE 属性
        $realm_attr = $this->createRealmAttribute();
        $nonce_attr = $this->createNonceAttribute();
        $response_attributes .= $realm_attr . $nonce_attr;

        // 构造 MESSAGE-INTEGRITY 属性
        $message_integrity = $this->createMessageIntegrity($response_attributes, $transaction_id);
        $response_attributes .= $message_integrity;

        // 构造 UNKNOWN-ATTRIBUTES 属性（如果有未知属性）
        $unknown_attrs = $this->getUnknownAttributes($attributes);
        if (!empty($unknown_attrs)) {
            $unknown_attributes = $this->createUnknownAttributesAttribute($unknown_attrs);
            $response_attributes .= $unknown_attributes;
        }

        // 计算消息长度
        $message_length = strlen($response_attributes);

        // 构造 STUN 消息头
        $response_header = $this->buildResponseHeader(self::STUN_BINDING_RESPONSE, $message_length, $transaction_id);

        // 完整的 STUN 消息
        $response = $response_header . $response_attributes;

        // 发送响应
        $connection->send($response);
    }

    /**
     * 处理 Shared Secret Request 消息
     * @param UdpConnection $connection
     * @param string $transaction_id
     * @param array $attributes
     * @param string $client_ip
     * @param int $client_port
     */
    private function handleSharedSecretRequest(UdpConnection $connection, $transaction_id, $attributes, $client_ip, $client_port)
    {
        // TODO: 实现 Shared Secret Request 的处理逻辑
        Worker::log("Shared Secret Request not implemented yet from $client_ip:$client_port.");
        $this->sendErrorResponse($connection, $transaction_id, 501, 'Not Implemented');
    }

    /**
     * 解析 STUN 属性
     * @param string $data
     * @param int $offset
     * @param int $length
     * @return array
     */
    private function parseAttributes($data, $offset, $length)
    {
        $attributes = [];
        $end = $offset + $length;
        while ($offset + 4 <= strlen($data) && $offset + 4 <= $end) {
            $attr = unpack('nType/nLength', substr($data, $offset, 4));
            $attr_type = $attr['Type'];
            $attr_length = $attr['Length'];

            if ($offset + 4 + $attr_length > strlen($data) || $offset + 4 + $attr_length > $end) {
                Worker::log("Attribute length exceeds message bounds.");
                break;
            }

            $attr_value = substr($data, $offset + 4, $attr_length);
            $attributes[$attr_type] = [
                'value' => $attr_value,
                'offset' => $offset + 4 + $attr_length,
            ];

            // 属性长度需要对齐到 4 字节
            $offset += 4 + $attr_length;
            if ($attr_length % 4 != 0) {
                $offset += (4 - ($attr_length % 4));
            }
        }
        return $attributes;
    }

    /**
     * 创建 XOR-MAPPED-ADDRESS 属性
     * @param string $ip
     * @param int $port
     * @param string $transaction_id
     * @return string
     */
    private function createXorMappedAddress($ip, $port, $transaction_id)
    {
        $family = (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) ? 0x02 : 0x01;
        $port_xor = $port ^ (self::STUN_MAGIC_COOKIE >> 16);
        if ($family === 0x01) { // IPv4
            $ip_packed = ip2long($ip);
            $ip_xor = $ip_packed ^ self::STUN_MAGIC_COOKIE;
            $address = pack('C C n N', 0x00, $family, $port_xor, $ip_xor);
        } else { // IPv6
            $ip_packed = inet_pton($ip);
            $magic_cookie_packed = pack('N', self::STUN_MAGIC_COOKIE);
            $xored_ip = $this->xorBuffer($ip_packed, $magic_cookie_packed . $transaction_id);
            $address = pack('C C n', 0x00, $family, $port_xor) . $xored_ip;
        }

        // 构造属性
        $attr_type = self::STUN_ATTR_XOR_MAPPED_ADDRESS;
        $attr_length = strlen($address);
        $attribute = pack('n n', $attr_type, $attr_length) . $address;

        // 添加填充
        $attribute = $this->padToFourBytes($attribute);

        return $attribute;
    }

    /**
     * 创建 CHANGED-ADDRESS 属性
     * @param string $ip
     * @param int $port
     * @param string $transaction_id
     * @param bool $change_ip
     * @param bool $change_port
     * @return string
     */
    private function createChangedAddress($ip, $port, $transaction_id, $change_ip = false, $change_port = false)
    {
        // 根据变化请求改变 IP 和/或端口
        // 这里需要根据服务器的实际配置返回不同的地址和端口
        // 示例中简单地改变端口
        $changed_ip = $ip;
        $changed_port = $port;

        if ($change_ip) {
            // 示例：假设服务器有多个 IP，可以循环或随机选择一个变化后的 IP
            // 这里以不改变 IP 为示例
            // $changed_ip = '变化后的IP';
        }

        if ($change_port) {
            // 示例：简单地将端口加1
            $changed_port = ($port + 1) % 65536;
        }

        $family = (filter_var($changed_ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) ? 0x02 : 0x01;
        if ($family === 0x01) { // IPv4
            $ip_packed = ip2long($changed_ip);
            $address = pack('C C n N', 0x00, $family, $changed_port, $ip_packed);
        } else { // IPv6
            $ip_packed = inet_pton($changed_ip);
            $address = pack('C C n', 0x00, $family, $changed_port) . $ip_packed;
        }

        // 构造属性
        $attr_type = self::STUN_ATTR_CHANGED_ADDRESS;
        $attr_length = strlen($address);
        $attribute = pack('n n', $attr_type, $attr_length) . $address;

        // 添加填充
        $attribute = $this->padToFourBytes($attribute);

        return $attribute;
    }

    /**
     * 创建 MESSAGE-INTEGRITY 属性
     * @param string $attributes
     * @param string $transaction_id
     * @return string
     */
    private function createMessageIntegrity($attributes, $transaction_id)
    {
        // 根据 RFC 5389，MESSAGE-INTEGRITY 是 HMAC-SHA1 over STUN message up to (but excluding) MESSAGE-INTEGRITY attribute
        $message = $this->buildResponseHeader(self::STUN_BINDING_RESPONSE, strlen($attributes), $transaction_id) . $attributes;
        $hmac = hash_hmac('sha1', $message, $this->sharedSecret, true);
        // HMAC-SHA1 输出 20 字节，需对齐到 4 字节
        $hmac_padded = $this->padToFourBytes($hmac);

        // 构造 MESSAGE-INTEGRITY 属性
        $attr_type = self::STUN_ATTR_MESSAGE_INTEGRITY;
        $attr_length = strlen($hmac);
        $attribute = pack('n n', $attr_type, $attr_length) . $hmac;

        // 添加填充
        $attribute = $this->padToFourBytes($attribute);

        return $attribute;
    }

    /**
     * 创建 REALM 属性
     * @return string
     */
    private function createRealmAttribute()
    {
        $realm = $this->realm;
        $realm_padded = $this->padToFourBytes($realm);

        // 构造 REALM 属性
        $attr_type = self::STUN_ATTR_REALM;
        $attr_length = strlen($realm);
        $attribute = pack('n n', $attr_type, $attr_length) . $realm_padded;

        // 添加填充
        $attribute = $this->padToFourBytes($attribute);

        return $attribute;
    }

    /**
     * 创建 NONCE 属性
     * @return string
     */
    private function createNonceAttribute()
    {
        $nonce = $this->nonce;
        $nonce_padded = $this->padToFourBytes($nonce);

        // 构造 NONCE 属性
        $attr_type = self::STUN_ATTR_NONCE;
        $attr_length = strlen($nonce);
        $attribute = pack('n n', $attr_type, $attr_length) . $nonce_padded;

        // 添加填充
        $attribute = $this->padToFourBytes($attribute);

        return $attribute;
    }

    /**
     * 创建 UNKNOWN-ATTRIBUTES 属性
     * @param array $attributes
     * @return string
     */
    private function createUnknownAttributesAttribute($attributes)
    {
        $unknown_attrs = [];
        foreach ($attributes as $type => $attr) {
            if (!in_array($type, [
                self::STUN_ATTR_MAPPED_ADDRESS,
                self::STUN_ATTR_XOR_MAPPED_ADDRESS,
                self::STUN_ATTR_CHANGE_REQUEST,
                self::STUN_ATTR_SOURCE_ADDRESS,
                self::STUN_ATTR_CHANGED_ADDRESS,
                self::STUN_ATTR_USERNAME,
                self::STUN_ATTR_PASSWORD,
                self::STUN_ATTR_MESSAGE_INTEGRITY,
                self::STUN_ATTR_ERROR_CODE,
                self::STUN_ATTR_REALM,
                self::STUN_ATTR_NONCE,
                self::STUN_ATTR_UNKNOWN_ATTRIBUTES,
                self::STUN_ATTR_XOR_RELAYED_ADDRESS,
            ])) {
                $unknown_attrs[] = $type;
            }
        }

        if (empty($unknown_attrs)) {
            return '';
        }

        // 构造 UNKNOWN-ATTRIBUTES 属性
        $attr_type = self::STUN_ATTR_UNKNOWN_ATTRIBUTES;
        $attr_length = count($unknown_attrs) * 2; // 每个类型占2字节
        $attr_values = '';
        foreach ($unknown_attrs as $type) {
            $attr_values .= pack('n', $type);
        }
        $attribute = pack('n n', $attr_type, $attr_length) . $attr_values;

        // 添加填充
        $attribute = $this->padToFourBytes($attribute);

        return $attribute;
    }

    /**
     * 创建 ERROR-CODE 响应
     * @param string $transaction_id
     * @param int $error_code
     * @param string $reason
     * @return string
     */
    private function sendErrorResponse(UdpConnection $connection, $transaction_id, $error_code, $reason)
    {
        // 构造 ERROR-CODE 属性
        $error_class = intdiv($error_code, 100);
        $error_number = $error_code % 100;
        $error_code_packed = (100 * $error_class) + $error_number;

        $error_reason_padded = $this->padToFourBytes($reason);

        $error_code_attribute = pack('n n C C a*',
            self::STUN_ATTR_ERROR_CODE,
            4 + strlen($error_reason_padded),
            0, // Reserved
            $error_code_packed,
            $error_reason_padded
        );

        // 计算消息长度
        $message_length = strlen($error_code_attribute);

        // 构造 STUN 消息头
        $response_header = $this->buildResponseHeader(self::STUN_BINDING_ERROR_RESPONSE, $message_length, $transaction_id);

        // 完整的 STUN 消息
        $response = $response_header . $error_code_attribute;

        // 发送响应
        $connection->send($response);
    }

    /**
     * 验证 MESSAGE-INTEGRITY
     * @param array $attributes
     * @param string $message_up_to_integrity
     * @return bool
     */
    private function authenticate($attributes, $connection)
    {
        if (!isset($attributes[self::STUN_ATTR_MESSAGE_INTEGRITY])) {
            return false;
        }

        // 提取 USERNAME
        if (!isset($attributes[self::STUN_ATTR_USERNAME])) {
            return false;
        }
        $username = $attributes[self::STUN_ATTR_USERNAME]['value'];

        // 提取 PASSWORD（在实际应用中，应该通过 USERNAME 查询对应的密码）
        // 此处假设 $this->users 存储了 USERNAME => PASSWORD 的映射
        if (!isset($this->users[$username])) {
            return false;
        }
        $password = $this->users[$username];

        // 构造 key 为 PASSWORD
        $key = $password;

        // 提取 MESSAGE-INTEGRITY 属性的位置
        $mi_attr = $attributes[self::STUN_ATTR_MESSAGE_INTEGRITY];
        $mi_offset = $mi_attr['offset'] - strlen($mi_attr['value']); // 前4字节为属性头

        // 计算 HMAC-SHA1
        $hmac = hash_hmac('sha1', substr($connection->getLastMessage(), 0, $mi_offset), $key, true);

        // 比较 HMAC
        return hash_equals($hmac, $mi_attr['value']);
    }

    /**
     * 构造响应消息头
     * @param int $type
     * @param int $length
     * @param string $transaction_id
     * @return string
     */
    private function buildResponseHeader($type, $length, $transaction_id)
    {
        return pack('n n N', $type, $length, self::STUN_MAGIC_COOKIE) . $transaction_id;
    }

    /**
     * 对数据进行 4 字节对齐填充
     * @param string $data
     * @return string
     */
    private function padToFourBytes($data)
    {
        $padding = (4 - (strlen($data) % 4)) % 4;
        return $data . str_repeat("\0", $padding);
    }

    /**
     * XOR 操作，用于构造 IPv6 的地址
     * @param string $buffer1
     * @param string $buffer2
     * @return string
     */
    private function xorBuffer($buffer1, $buffer2)
    {
        $result = '';
        for ($i = 0; $i < strlen($buffer1); $i++) {
            $result .= $buffer1[$i] ^ $buffer2[$i];
        }
        return $result;
    }
}
