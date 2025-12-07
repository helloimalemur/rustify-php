<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Rustify\Option;
use Rustify\UnwrapException;

final class OptionTest extends TestCase
{
    public function testSomeBasics(): void
    {
        $opt = Option::some(41);
        $this->assertTrue($opt->isSome());
        $this->assertFalse($opt->isNone());
        $this->assertSame(41, $opt->unwrap());
    }

    public function testNoneBasicsAndUnwrap(): void
    {
        $opt = Option::none();
        $this->assertFalse($opt->isSome());
        $this->assertTrue($opt->isNone());
        $this->assertSame(42, $opt->unwrapOr(42));
        $this->assertSame(43, $opt->unwrapOrElse(fn() => 43));
        $this->expectException(UnwrapException::class);
        $opt->unwrap();
    }

    public function testFromNullable(): void
    {
        $some = Option::fromNullable('v');
        $none = Option::fromNullable(null);
        $this->assertTrue($some->isSome());
        $this->assertTrue($none->isNone());
    }

    public function testMapVariants(): void
    {
        $some = Option::some(2);
        $none = Option::none();

        $this->assertTrue($some->map(fn($x) => $x + 1)->isSome());
        $this->assertSame(10, $some->mapOr(10, fn($x) => $x * 5));
        $this->assertSame(10, $none->mapOr(10, fn($x) => $x * 5));
        $this->assertSame(11, $none->mapOrElse(fn() => 11, fn($x) => $x * 5));
    }

    public function testAndThenAndOrElseValue(): void
    {
        $some = Option::some(3);
        $none = Option::none();

        $next = $some->andThen(fn($x) => $x > 0 ? Option::some($x + 1) : Option::none());
        $this->assertTrue($next->isSome());
        $this->assertSame(4, $next->unwrap());

        $fallback = Option::some(99);
        $this->assertSame(3, $some->orElseValue($fallback)->unwrap());
        $this->assertSame(99, $none->orElseValue($fallback)->unwrap());
    }

    public function testOrElseLazy(): void
    {
        $called = 0;
        $none = Option::none();
        $res = $none->orElse(function () use (&$called) {
            $called++;
            return Option::some('x');
        });
        $this->assertSame(1, $called);
        $this->assertSame('x', $res->unwrap());

        $some = Option::some('y');
        $res2 = $some->orElse(function () use (&$called) {
            $called++;
            return Option::some('never');
        });
        $this->assertSame(1, $called, 'orElse must not be called for Some');
        $this->assertSame('y', $res2->unwrap());
    }

    public function testExpect(): void
    {
        $this->assertSame(1, Option::some(1)->expect('nope'));
        $this->expectException(UnwrapException::class);
        $this->expectExceptionMessage('boom');
        Option::none()->expect('boom');
    }

    public function testIfSomeIfNone(): void
    {
        $hit = 0;
        Option::some(1)->ifSome(function () use (&$hit) { $hit++; });
        Option::some(1)->ifNone(function () use (&$hit) { $hit++; });
        Option::none()->ifSome(function () use (&$hit) { $hit++; });
        Option::none()->ifNone(function () use (&$hit) { $hit++; });
        $this->assertSame(2, $hit);
    }

    public function testAndThenMustReturnOption(): void
    {
        $this->expectException(UnwrapException::class);
        Option::some(1)->andThen(fn($x) => $x + 1); // not an Option
    }

    public function testOrElseCallbackMustReturnOption(): void
    {
        $this->expectException(UnwrapException::class);
        Option::none()->orElse(fn() => 123); // not an Option
    }

    public function testToNullable(): void
    {
        $this->assertSame(5, Option::some(5)->toNullable());
        $this->assertSame(0, Option::some(0)->toNullable());
        $this->assertSame(false, Option::some(false)->toNullable());
        $this->assertNull(Option::none()->toNullable());
    }

    public function testFromNullableRoundTrip(): void
    {
        $this->assertSame('x', Option::fromNullable('x')->toNullable());
        $this->assertNull(Option::fromNullable(null)->toNullable());
    }
}
