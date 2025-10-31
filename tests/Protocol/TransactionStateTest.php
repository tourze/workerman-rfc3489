<?php

namespace Tourze\Workerman\RFC3489\Tests\Protocol;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\Workerman\RFC3489\Protocol\TransactionState;

/**
 * TransactionState 测试类
 *
 * @internal
 *
 * @phpstan-ignore-next-line 这是一个UnitEnum测试，不继承AbstractEnumTestCase
 */
#[CoversClass(TransactionState::class)]
final class TransactionStateTest extends TestCase
{
    public function testEnumCases(): void
    {
        $this->assertSame('PENDING', TransactionState::PENDING->name);
        $this->assertSame('COMPLETED', TransactionState::COMPLETED->name);
        $this->assertSame('TIMEOUT', TransactionState::TIMEOUT->name);
        $this->assertSame('FAILED', TransactionState::FAILED->name);
        $this->assertSame('CANCELLED', TransactionState::CANCELLED->name);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('待处理', TransactionState::PENDING->getLabel());
        $this->assertSame('已完成', TransactionState::COMPLETED->getLabel());
        $this->assertSame('超时', TransactionState::TIMEOUT->getLabel());
        $this->assertSame('失败', TransactionState::FAILED->getLabel());
        $this->assertSame('取消', TransactionState::CANCELLED->getLabel());
    }

    public function testToArray(): void
    {
        $array = TransactionState::PENDING->toArray();
        $this->assertArrayHasKey('value', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertSame('pending', $array['value']);
        $this->assertSame('待处理', $array['label']);
        $this->assertCount(2, $array);
    }

    public function testNameUniqueness(): void
    {
        $names = [];
        foreach (TransactionState::cases() as $case) {
            $this->assertNotContains($case->name, $names, sprintf('Duplicate name "%s" found', $case->name));
            $names[] = $case->name;
        }
    }

    public function testLabelUniqueness(): void
    {
        $labels = array_map(fn ($case) => $case->getLabel(), TransactionState::cases());
        $this->assertSame(count($labels), count(array_unique($labels)), '所有枚举的 label 必须是唯一的。');
    }
}
