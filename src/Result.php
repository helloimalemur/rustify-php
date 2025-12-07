<?php

declare(strict_types=1);

namespace Rustify;

/**
 * @template T
 * @template E
 */
abstract class Result
{
    abstract public function isOk(): bool;

    abstract public function isErr(): bool;

    /**
     * Construct an Ok.
     *
     * @template U
     * @template F
     * @param U $value
     * @return Result<U, F>
     */
    public static function ok(mixed $value): Result
    {
        /** @var Result<U, F> */
        return new Ok($value);
    }

    /**
     * Construct an Err.
     *
     * @template U
     * @template F
     * @param F $error
     * @return Result<U, F>
     */
    public static function err(mixed $error): Result
    {
        /** @var Result<U, F> */
        return new Err($error);
    }

    /**
     * Map Ok value; Err is propagated.
     *
     * @template U
     * @param callable(T):U $f
     * @return Result<U, E>
     */
    abstract public function map(callable $f): Result;

    /**
     * Map Err value; Ok is propagated.
     *
     * @template F
     * @param callable(E):F $f
     * @return Result<T, F>
     */
    abstract public function mapErr(callable $f): Result;

    /**
     * and_then / flatMap for Ok.
     *
     * @template U
     * @param callable(T):Result<U, E> $f
     * @return Result<U, E>
     */
    abstract public function andThen(callable $f): Result;

    /**
     * Return self if Ok, otherwise $resb (eager alternative to {@see Result::orElse}).
     *
     * @param Result<T, E> $resb
     * @return Result<T, E>
     */
    abstract public function orElseValue(Result $resb): Result;

    /**
     * Return self if Ok, otherwise result of $f($error).
     *
     * In the Err variant, the error value (E) is passed to the callback.
     *
     * @param callable(E):Result<T, E> $f
     * @return Result<T, E>
     */
    abstract public function orElse(callable $f): Result;

    /**
     * Unwrap Ok value or throw.
     *
     * @return T
     */
    abstract public function unwrap(): mixed;

    /**
     * Unwrap Err value or throw.
     *
     * @return E
     */
    abstract public function unwrapErr(): mixed;

    /**
     * Unwrap Ok or return $default.
     *
     * @template U
     * @param U $default
     * @return T|U
     */
    abstract public function unwrapOr(mixed $default): mixed;

    /**
     * Unwrap Ok or compute default via callable($error).
     *
     * For Err, the error value (E) is passed to the callable and its result is returned.
     *
     * @template U
     * @param callable(E):U $default
     * @return T|U
     */
    abstract public function unwrapOrElse(callable $default): mixed;

    /**
     * Unwrap Ok or throw with custom message.
     *
     * @return T
     */
    abstract public function expect(string $message): mixed;

    /**
     * Unwrap Err or throw with custom message.
     *
     * @return E
     */
    abstract public function expectErr(string $message): mixed;

    /**
     * Execute callback if Ok.
     *
     * @param callable(T):void $f
     */
    abstract public function ifOk(callable $f): void;

    /**
     * Execute callback if Err.
     *
     * @param callable(E):void $f
     */
    abstract public function ifErr(callable $f): void;
}

/**
 * @template T
 * @template E
 * @extends Result<T, E>
 */
final class Ok extends Result
{
    /** @var T */
    public mixed $value;

    /**
     * @param T $value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    public function isOk(): bool
    {
        return true;
    }

    public function isErr(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function map(callable $f): Result
    {
        return new Ok($f($this->value));
    }

    /** @inheritDoc */
    public function mapErr(callable $f): Result
    {
        return $this;
    }

    /** @inheritDoc */
    public function andThen(callable $f): Result
    {
        $res = $f($this->value);
        if (!$res instanceof Result) {
            throw new UnwrapException('andThen callback must return a Result');
        }
        return $res;
    }

    /** @inheritDoc */
    public function orElseValue(Result $resb): Result
    {
        return $this;
    }

    /** @inheritDoc */
    public function orElse(callable $f): Result
    {
        return $this;
    }

    /** @inheritDoc */
    public function unwrap(): mixed
    {
        return $this->value;
    }

    /** @inheritDoc */
    public function unwrapErr(): mixed
    {
        throw new UnwrapException('Called unwrapErr() on Ok');
    }

    /** @inheritDoc */
    public function unwrapOr(mixed $default): mixed
    {
        return $this->value;
    }

    /** @inheritDoc */
    public function unwrapOrElse(callable $default): mixed
    {
        return $this->value;
    }

    /** @inheritDoc */
    public function expect(string $message): mixed
    {
        return $this->value;
    }

    /** @inheritDoc */
    public function expectErr(string $message): mixed
    {
        throw new UnwrapException($message);
    }

    /** @inheritDoc */
    public function ifOk(callable $f): void
    {
        $f($this->value);
    }

    /** @inheritDoc */
    public function ifErr(callable $f): void
    {
        // no-op
    }
}

/**
 * @template T
 * @template E
 * @extends Result<T, E>
 */
final class Err extends Result
{
    /** @var E */
    public mixed $error;

    /**
     * @param E $error
     */
    public function __construct(mixed $error)
    {
        $this->error = $error;
    }

    public function isOk(): bool
    {
        return false;
    }

    public function isErr(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function map(callable $f): Result
    {
        return $this;
    }

    /** @inheritDoc */
    public function mapErr(callable $f): Result
    {
        return new Err($f($this->error));
    }

    /** @inheritDoc */
    public function andThen(callable $f): Result
    {
        return $this;
    }

    /** @inheritDoc */
    public function orElseValue(Result $resb): Result
    {
        return $resb;
    }

    /** @inheritDoc */
    public function orElse(callable $f): Result
    {
        $res = $f($this->error);
        if (!$res instanceof Result) {
            throw new UnwrapException('orElse callback must return a Result');
        }
        return $res;
    }

    /** @inheritDoc */
    public function unwrap(): mixed
    {
        throw new UnwrapException('Called unwrap() on Err');
    }

    /** @inheritDoc */
    public function unwrapErr(): mixed
    {
        return $this->error;
    }

    /** @inheritDoc */
    public function unwrapOr(mixed $default): mixed
    {
        return $default;
    }

    /** @inheritDoc */
    public function unwrapOrElse(callable $default): mixed
    {
        return $default($this->error);
    }

    /** @inheritDoc */
    public function expect(string $message): mixed
    {
        throw new UnwrapException($message);
    }

    /** @inheritDoc */
    public function expectErr(string $message): mixed
    {
        return $this->error;
    }

    /** @inheritDoc */
    public function ifOk(callable $f): void
    {
        // no-op
    }

    /** @inheritDoc */
    public function ifErr(callable $f): void
    {
        $f($this->error);
    }
}
