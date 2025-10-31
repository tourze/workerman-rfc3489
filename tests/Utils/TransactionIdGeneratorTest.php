<?php

namespace Tourze\Workerman\RFC3489\Tests\Utils;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Exception\InvalidArgumentException;
use Tourze\Workerman\RFC3489\Utils\TransactionIdGenerator;

/**
 * @internal
 */
#[CoversClass(TransactionIdGenerator::class)]
final class TransactionIdGeneratorTest extends TestCase
{
    public function testGenerate(): void
    {
        $id = TransactionIdGenerator::generate();

        // 事务ID应该是16个字节的字符串
        $this->assertSame(16, strlen($id));
        $this->assertIsString($id);
    }

    public function testGenerateMultiple(): void
    {
        // 生成多个ID并验证它们是否唯一
        $ids = [];
        $count = 10;

        for ($i = 0; $i < $count; ++$i) {
            $ids[] = TransactionIdGenerator::generate();
        }

        // 检查所有ID的长度
        foreach ($ids as $id) {
            $this->assertSame(16, strlen($id));
        }

        // 检查唯一性（转为十六进制以便进行比较，因为二进制可能包含不可打印字符）
        $hexIds = array_map('bin2hex', $ids);
        $uniqueHexIds = array_unique($hexIds);

        // 唯一ID的数量应该等于生成的ID数量
        $this->assertCount($count, $uniqueHexIds);
    }

    public function testGenerateWithLength(): void
    {
        $customLength = 8;
        $id = TransactionIdGenerator::generate($customLength);

        // 事务ID长度应该与指定的长度匹配
        $this->assertSame($customLength, strlen($id));
    }

    public function testGenerateWithInvalidLength(): void
    {
        $invalidLength = -1;

        // 设置期望抛出异常
        $this->expectException(InvalidArgumentException::class);

        // 尝试生成负长度的事务ID，应该抛出异常
        TransactionIdGenerator::generate($invalidLength);
    }
}
