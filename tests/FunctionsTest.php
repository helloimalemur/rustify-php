<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use function Rustify\{some, none, ok, err, option_match, result_match, if_some, if_ok};

final class FunctionsTest extends TestCase
{
    public function testShorthandConstructors(): void
    {
        $this->assertSame(5, some(5)->unwrap());
        $this->assertTrue(none()->isNone());
        $this->assertTrue(ok('v')->isOk());
        $this->assertTrue(err('e')->isErr());
    }

    public function testOptionMatch(): void
    {
        $a = option_match(some(7), fn($x) => $x + 1, fn() => 0);
        $b = option_match(none(), fn($x) => $x + 1, fn() => 0);
        $this->assertSame(8, $a);
        $this->assertSame(0, $b);
    }

    public function testResultMatch(): void
    {
        $a = result_match(ok('A'), fn($x) => $x, fn($e) => 'E');
        $b = result_match(err('x'), fn($x) => $x, fn($e) => 'E');
        $this->assertSame('A', $a);
        $this->assertSame('E', $b);
    }

    public function testIfSomeAndIfOk(): void
    {
        $hit = 0;
        if_some(some(1), function () use (&$hit) { $hit++; });
        if_ok(ok(1), function () use (&$hit) { $hit++; });
        if_some(none(), function () use (&$hit) { $hit++; });
        if_ok(err('e'), function () use (&$hit) { $hit++; });
        $this->assertSame(2, $hit);
    }
}
