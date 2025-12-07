<?php

declare(strict_types=1);

namespace Rustify;

/**
 * @template T
 */
abstract class Option
{
    /**
     * True if this is a Some variant.
     */
    abstract public function isSome(): bool;

    /**
     * True if this is a None variant.
     */
    abstract public function isNone(): bool;

    /**
     * Construct a Some.
     *
     * @template U
     * @param U $value
     * @return Option<U>
     */
    public static function some(mixed $value): Option
    {
        return new Some($value);
    }

    /**
     * Construct a None.
     *
     * @template U
     * @return Option<U>
     */
    public static function none(): Option
    {
        return new None();
    }

    /**
     * Lift a nullable value into Option.
     * null -> None, otherwise Some($value)
     *
     * @template U
     * @param U|null $value
     * @return Option<U>
     */
    public static function fromNullable(mixed $value): Option
    {
        return $value === null ? new None() : new Some($value);
    }

    /**
     * Map the inner value if Some, otherwise propagate None.
     *
     * @template U
     * @param callable(T):U $f
     * @return Option<U>
     */
    abstract public function map(callable $f): Option;

    /**
     * Map the inner value if Some, otherwise return $default.
     *
     * @template U
     * @param U $default
     * @param callable(T):U $f
     * @return U
     */
    abstract public function mapOr(mixed $default, callable $f): mixed;

    /**
     * map_or_else: lazy default.
     *
     * @template U
     * @param callable():U $default
     * @param callable(T):U $f
     * @return U
     */
    abstract public function mapOrElse(callable $default, callable $f): mixed;

    /**
     * and_then / flatMap: chain operations returning Option.
     *
     * @template U
     * @param callable(T):Option<U> $f
     * @return Option<U>
     */
    abstract public function andThen(callable $f): Option;

    /**
     * Return self if Some, otherwise $optb.
     *
     * @param Option<T> $optb
     * @return Option<T>
     */
    abstract public function orElseValue(Option $optb): Option;

    /**
     * Return self if Some, otherwise result of $f().
     *
     * @param callable():Option<T> $f
     * @return Option<T>
     */
    abstract public function orElse(callable $f): Option;

    /**
     * Unwrap the value or throw.
     *
     * @return T
     */
    abstract public function unwrap(): mixed;

    /**
     * Unwrap or return $default if None.
     *
     * @template U
     * @param U $default
     * @return T|U
     */
    abstract public function unwrapOr(mixed $default): mixed;

    /**
     * Unwrap or compute default via callable.
     *
     * @template U
     * @param callable():U $default
     * @return T|U
     */
    abstract public function unwrapOrElse(callable $default): mixed;

    /**
     * Unwrap or throw with custom message.
     *
     * @return T
     */
    abstract public function expect(string $message): mixed;

    /**
     * Execute callback if Some.
     *
     * @param callable(T):void $f
     */
    abstract public function ifSome(callable $f): void;

    /**
     * Execute callback if None.
     *
     * @param callable():void $f
     */
    abstract public function ifNone(callable $f): void;
}

/**
 * @template T
 * @extends Option<T>
 */
final class Some extends Option
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

    public function isSome(): bool
    {
        return true;
    }

    public function isNone(): bool
    {
        return false;
    }

    /** @inheritDoc */
    public function map(callable $f): Option
    {
        return new Some($f($this->value));
    }

    /** @inheritDoc */
    public function mapOr(mixed $default, callable $f): mixed
    {
        return $f($this->value);
    }

    /** @inheritDoc */
    public function mapOrElse(callable $default, callable $f): mixed
    {
        return $f($this->value);
    }

    /** @inheritDoc */
    public function andThen(callable $f): Option
    {
        $res = $f($this->value);
        if (!$res instanceof Option) {
            throw new UnwrapException('andThen callback must return an Option');
        }
        return $res;
    }

    /** @inheritDoc */
    public function orElseValue(Option $optb): Option
    {
        return $this;
    }

    /** @inheritDoc */
    public function orElse(callable $f): Option
    {
        return $this;
    }

    /** @inheritDoc */
    public function unwrap(): mixed
    {
        return $this->value;
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
    public function ifSome(callable $f): void
    {
        $f($this->value);
    }

    /** @inheritDoc */
    public function ifNone(callable $f): void
    {
        // no-op
    }
}

/**
 * @template T
 * @extends Option<T>
 */
final class None extends Option
{
    public function isSome(): bool
    {
        return false;
    }

    public function isNone(): bool
    {
        return true;
    }

    /** @inheritDoc */
    public function map(callable $f): Option
    {
        return $this;
    }

    /** @inheritDoc */
    public function mapOr(mixed $default, callable $f): mixed
    {
        return $default;
    }

    /** @inheritDoc */
    public function mapOrElse(callable $default, callable $f): mixed
    {
        return $default();
    }

    /** @inheritDoc */
    public function andThen(callable $f): Option
    {
        return $this;
    }

    /** @inheritDoc */
    public function orElseValue(Option $optb): Option
    {
        return $optb;
    }

    /** @inheritDoc */
    public function orElse(callable $f): Option
    {
        $res = $f();
        if (!$res instanceof Option) {
            throw new UnwrapException('orElse callback must return an Option');
        }
        return $res;
    }

    /** @inheritDoc */
    public function unwrap(): mixed
    {
        throw new UnwrapException('Called unwrap() on None');
    }

    /** @inheritDoc */
    public function unwrapOr(mixed $default): mixed
    {
        return $default;
    }

    /** @inheritDoc */
    public function unwrapOrElse(callable $default): mixed
    {
        return $default();
    }

    /** @inheritDoc */
    public function expect(string $message): mixed
    {
        throw new UnwrapException($message);
    }

    /** @inheritDoc */
    public function ifSome(callable $f): void
    {
        // no-op
    }

    /** @inheritDoc */
    public function ifNone(callable $f): void
    {
        $f();
    }
}
