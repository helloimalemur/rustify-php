<?php

declare(strict_types=1);

namespace Rustify;

require_once __DIR__ . '/Option.php';
require_once __DIR__ . '/Result.php';
require_once __DIR__ . '/Exceptions.php';

/**
 * Shorthand constructors so you can write:
 *   ok($v), err($e), some($v), none()
 */

/**
 * @template T
 * @param T $value
 * @return Option<T>
 */
function some(mixed $value): Option
{
    return Option::some($value);
}

/**
 * @template T
 * @return Option<T>
 */
function none(): Option
{
    return Option::none();
}

/**
 * @template T
 * @template E
 * @param T $value
 * @return Result<T, E>
 */
function ok(mixed $value): Result
{
    return Result::ok($value);
}

/**
 * @template T
 * @template E
 * @param E $error
 * @return Result<T, E>
 */
function err(mixed $error): Result
{
    return Result::err($error);
}

/**
 * "if let Some(x)" style.
 *
 * @template T
 * @param Option<T> $opt
 * @param callable(T):void $f
 */
function if_some(Option $opt, callable $f): void
{
    if ($opt->isSome()) {
        $f($opt->unwrap());
    }
}

/**
 * "if let Ok(x)" style.
 *
 * @template T
 * @template E
 * @param Result<T, E> $res
 * @param callable(T):void $f
 */
function if_ok(Result $res, callable $f): void
{
    if ($res->isOk()) {
        $f($res->unwrap());
    }
}

/**
 * Simple match for Option.
 *
 * @template T
 * @template U
 * @param Option<T> $opt
 * @param callable(T):U $onSome
 * @param callable():U $onNone
 * @return U
 */
function option_match(Option $opt, callable $onSome, callable $onNone): mixed
{
    if ($opt->isSome()) {
        return $onSome($opt->unwrap());
    }
    return $onNone();
}

/**
 * Simple match for Result.
 *
 * @template T
 * @template E
 * @template U
 * @param Result<T,E> $res
 * @param callable(T):U $onOk
 * @param callable(E):U $onErr
 * @return U
 */
function result_match(Result $res, callable $onOk, callable $onErr): mixed
{
    if ($res->isOk()) {
        return $onOk($res->unwrap());
    }
    return $onErr($res->unwrapErr());
}
