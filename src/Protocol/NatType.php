<?php

namespace Tourze\Workerman\RFC3489\Protocol;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * NAT类型枚举类
 *
 * 定义RFC3489中描述的各种NAT类型
 */
enum NatType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    /**
     * 未知NAT类型
     */
    case UNKNOWN = 'Unknown';

    /**
     * 开放互联网（无NAT）
     *
     * 客户端直接连接到互联网，没有NAT
     */
    case OPEN_INTERNET = 'Open Internet';

    /**
     * 完全锥形NAT
     *
     * 一旦内部地址(iAddr:iPort)映射到外部地址(eAddr:ePort)，
     * 所有发自iAddr:iPort的数据包都经由eAddr:ePort向外发送，
     * 且任意外部主机都可以通过发送数据包到eAddr:ePort，
     * 将数据包发送到内部主机iAddr:iPort。
     */
    case FULL_CONE = 'Full Cone NAT';

    /**
     * 受限锥形NAT
     *
     * 一旦内部地址(iAddr:iPort)映射到外部地址(eAddr:ePort)，
     * 所有发自iAddr:iPort的数据包都经由eAddr:ePort向外发送，
     * 但是只有内部主机iAddr:iPort发送过数据包到地址为dAddr:dPort的目标主机后，
     * 外部主机dAddr:任意端口才能发送数据包到内部主机iAddr:iPort。
     */
    case RESTRICTED_CONE = 'Restricted Cone NAT';

    /**
     * 端口受限锥形NAT
     *
     * 与受限锥形NAT相比更严格，
     * 对于外部主机dAddr:dPort，只有在内部主机iAddr:iPort发送过数据包到dAddr:dPort后，
     * 外部主机dAddr:dPort才能发送数据包到内部主机iAddr:iPort。
     */
    case PORT_RESTRICTED_CONE = 'Port Restricted Cone NAT';

    /**
     * 对称NAT
     *
     * 每个来自相同内部地址和端口，但是发送到不同目标的请求，
     * 映射到不同的外部地址和端口。
     * 只有曾经收到过内部主机数据的外部主机，才可以把数据发送回内部主机。
     */
    case SYMMETRIC = 'Symmetric NAT';

    /**
     * 对称UDP防火墙
     *
     * 没有NAT但有防火墙，
     * 防火墙会记住内部主机向外部主机发送数据的记录，
     * 只允许接收发送过数据的目标主机回应的数据包。
     */
    case SYMMETRIC_UDP_FIREWALL = 'Symmetric UDP Firewall';

    /**
     * 阻塞UDP
     *
     * 所有UDP数据包被阻塞
     */
    case BLOCKED = 'Blocked';

    /**
     * 获取NAT类型的详细描述
     *
     * @return string 类型描述
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::UNKNOWN => '无法确定NAT类型',
            self::OPEN_INTERNET => '设备直接连接到互联网，没有NAT',
            self::FULL_CONE => '设备位于完全锥形NAT后面，任何外部主机都可以通过NAT映射地址访问内部主机',
            self::RESTRICTED_CONE => '设备位于受限锥形NAT后面，只有内部主机发送过数据的外部IP才能发送数据回内部主机',
            self::PORT_RESTRICTED_CONE => '设备位于端口受限锥形NAT后面，只有内部主机发送过数据的特定外部IP和端口才能发送数据回内部主机',
            self::SYMMETRIC => '设备位于对称NAT后面，对每个外部目标使用不同的映射，最难以进行P2P通信',
            self::SYMMETRIC_UDP_FIREWALL => '设备有对称UDP防火墙，允许接收发送过数据的目标主机回应的数据包',
            self::BLOCKED => '所有UDP数据包被阻塞，无法进行UDP通信',
        };
    }

    /**
     * 检查是否支持P2P通信
     *
     * @return bool 如果支持P2P通信则返回true
     */
    public function isSupportP2P(): bool
    {
        return match ($this) {
            self::OPEN_INTERNET, self::FULL_CONE,
            self::RESTRICTED_CONE, self::PORT_RESTRICTED_CONE => true,
            self::SYMMETRIC, self::SYMMETRIC_UDP_FIREWALL,
            self::BLOCKED, self::UNKNOWN => false,
        };
    }

    /**
     * 获取P2P通信建议
     *
     * @return string P2P通信建议
     */
    public function getP2PAdvice(): string
    {
        return match ($this) {
            self::OPEN_INTERNET => '可以直接进行P2P通信，无需NAT穿透',
            self::FULL_CONE => '可以通过简单的端口映射进行P2P通信',
            self::RESTRICTED_CONE => '可以使用标准STUN进行P2P通信，但需要双方都发起连接',
            self::PORT_RESTRICTED_CONE => '可以使用标准STUN进行P2P通信，但需要精确的端口预测和双方发起连接',
            self::SYMMETRIC => '难以进行P2P通信，建议使用TURN服务器中继',
            self::SYMMETRIC_UDP_FIREWALL => '需要使用TCP或TURN服务器中继进行通信',
            self::BLOCKED => '无法使用UDP，需要使用TCP或TURN服务器中继进行通信',
            self::UNKNOWN => '建议先尝试直接连接，若失败则使用TURN服务器中继',
        };
    }

    /**
     * 获取NAT类型的标签（实现Labelable接口）
     *
     * @return string NAT类型标签
     */
    public function getLabel(): string
    {
        return $this->value;
    }
}
