<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Rustify\Result;
use Rustify\UnwrapException;

final class ResultTest extends TestCase
{
    public function testOkAndErrBasics(): void
    {
        $ok = Result::ok('v');
        $err = Result::err('e');
        $this->assertTrue($ok->isOk());
        $this->assertFalse($ok->isErr());
        $this->assertFalse($err->isOk());
        $this->assertTrue($err->isErr());
    }

    public function testUnwrapAndUnwrapErr(): void
    {
        $ok = Result::ok(123);
        $this->assertSame(123, $ok->unwrap());

        $err = Result::err('boom');
        $this->assertSame('boom', $err->unwrapErr());

        $this->expectException(UnwrapException::class);
        $err->unwrap();
    }

    public function testUnwrapOrVariants(): void
    {
        $this->assertSame(1, Result::ok(1)->unwrapOr(0));
        $this->assertSame(0, Result::err('x')->unwrapOr(0));
        $this->assertSame(5, Result::ok(5)->unwrapOrElse(fn() => 10));
        $this->assertSame(10, Result::err('x')->unwrapOrElse(fn() => 10));
    }

    public function testExpectAndExpectErr(): void
    {
        $this->assertSame(7, Result::ok(7)->expect('nope'));
        $this->assertSame('e', Result::err('e')->expectErr('nope'));

        $this->expectException(UnwrapException::class);
        $this->expectExceptionMessage('boom');
        Result::err('e')->expect('boom');
    }

    public function testMapMapErrAndThen(): void
    {
        $ok = Result::ok(2)
            ->map(fn($x) => $x + 1)
            ->andThen(fn($x) => $x > 2 ? Result::ok($x * 2) : Result::err('small'));
        $this->assertTrue($ok->isOk());
        $this->assertSame(6, $ok->unwrap());

        $err = Result::err('x')->mapErr(fn($e) => strtoupper((string)$e));
        $this->assertTrue($err->isErr());
        $this->assertSame('X', $err->unwrapErr());
    }

    public function testOrElseVariants(): void
    {
        $fallback = Result::ok('fallback');
        $this->assertSame('v', Result::ok('v')->orElseValue($fallback)->unwrap());
        $this->assertSame('fallback', Result::err('e')->orElseValue($fallback)->unwrap());

        $called = 0;
        $recovered = Result::err('missing')->orElse(function ($e) use (&$called) {
            $called++;
            return Result::ok("fixed: $e");
        });
        $this->assertSame(1, $called);
        $this->assertSame('fixed: missing', $recovered->unwrap());

        $stillOk = Result::ok('yes')->orElse(function () use (&$called) {
            $called++;
            return Result::ok('no');
        });
        $this->assertSame(1, $called, 'orElse must not be called for Ok');
        $this->assertSame('yes', $stillOk->unwrap());
    }

    public function testAndThenMustReturnResult(): void
    {
        $this->expectException(UnwrapException::class);
        Result::ok(1)->andThen(fn($x) => $x + 1); // not a Result
    }

    public function testOrElseCallbackMustReturnResult(): void
    {
        $this->expectException(UnwrapException::class);
        Result::err('e')->orElse(fn() => 123); // not a Result
    }

    public function testIfOkIfErr(): void
    {
        $hits = 0;
        Result::ok(1)->ifOk(function () use (&$hits) { $hits++; });
        Result::ok(1)->ifErr(function () use (&$hits) { $hits++; });
        Result::err('e')->ifOk(function () use (&$hits) { $hits++; });
        Result::err('e')->ifErr(function () use (&$hits) { $hits++; });
        $this->assertSame(2, $hits);
    }
}
