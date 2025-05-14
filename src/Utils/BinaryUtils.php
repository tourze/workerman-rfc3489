<?php

namespace Tourze\Workerman\RFC3489\Utils;

/**
 * 二进制数据处理工具类
 *
 * 提供处理二进制数据的实用方法
 */
class BinaryUtils
{
    /**
     * 读取无符号16位整数（网络字节序）
     *
     * @param string $data 二进制数据
     * @param int $offset 起始偏移量
     * @return int 16位无符号整数
     */
    public static function readUint16(string $data, int $offset): int
    {
        return unpack('n', substr($data, $offset, 2))[1];
    }

    /**
     * 写入无符号16位整数（网络字节序）
     *
     * @param int $value 16位无符号整数
     * @return string 编码后的二进制数据
     */
    public static function writeUint16(int $value): string
    {
        return pack('n', $value);
    }

    /**
     * 读取无符号32位整数（网络字节序）
     *
     * @param string $data 二进制数据
     * @param int $offset 起始偏移量
     * @return int 32位无符号整数
     */
    public static function readUint32(string $data, int $offset): int
    {
        return unpack('N', substr($data, $offset, 4))[1];
    }

    /**
     * 写入无符号32位整数（网络字节序）
     *
     * @param int $value 32位无符号整数
     * @return string 编码后的二进制数据
     */
    public static function writeUint32(int $value): string
    {
        return pack('N', $value);
    }

    /**
     * 读取无符号8位整数
     *
     * @param string $data 二进制数据
     * @param int $offset 起始偏移量
     * @return int 8位无符号整数
     */
    public static function readUint8(string $data, int $offset): int
    {
        return ord($data[$offset]);
    }

    /**
     * 写入无符号8位整数
     *
     * @param int $value 8位无符号整数
     * @return string 编码后的二进制数据
     */
    public static function writeUint8(int $value): string
    {
        return chr($value);
    }

    /**
     * 填充数据到指定长度
     *
     * @param string $data 原始数据
     * @param int $length 目标长度
     * @param string $padChar 填充字符
     * @return string 填充后的数据
     */
    public static function pad(string $data, int $length, string $padChar = "\0"): string
    {
        $padLength = $length - strlen($data);
        if ($padLength <= 0) {
            return $data;
        }

        return $data . str_repeat($padChar, $padLength);
    }

    /**
     * 计算填充后的长度
     *
     * @param int $length 原始长度
     * @param int $alignment 对齐字节数
     * @return int 填充后的长度
     */
    public static function getPaddedLength(int $length, int $alignment): int
    {
        $remainder = $length % $alignment;
        if ($remainder === 0) {
            return $length;
        }

        return $length + ($alignment - $remainder);
    }

    /**
     * 检查是否需要字节序转换（判断系统是否为小端字节序）
     *
     * @return bool 如果系统是小端字节序，返回true
     */
    public static function needsByteSwap(): bool
    {
        return unpack('S', "\x01\x00")[1] === 1;
    }

    /**
     * 交换字节序（16位整数）
     *
     * @param int $value 16位整数
     * @return int 字节序转换后的值
     */
    public static function swapBytes16(int $value): int
    {
        return (($value & 0xff) << 8) | (($value >> 8) & 0xff);
    }

    /**
     * 交换字节序（32位整数）
     *
     * @param int $value 32位整数
     * @return int 字节序转换后的值
     */
    public static function swapBytes32(int $value): int
    {
        return (($value & 0xff) << 24) |
            (($value & 0xff00) << 8) |
            (($value >> 8) & 0xff00) |
            (($value >> 24) & 0xff);
    }
}
