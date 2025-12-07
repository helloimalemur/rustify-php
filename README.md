# rustify-php

Rust-like `Option` and `Result` types for PHP 8+.

Fully compatible with typical PHP code

You can:
•	store Option or Result in arrays
•	return them from any function
•	use them as method return types
•	wrap exceptions with them
•	integrate with your Rust backend patterns (structural symmetry)

Option and Result behave like normal objects but with Rust semantics.

## Install

```bash
composer require helloimalemur/rustify-php
```

## Usage

```php
use Rustify\Json;
use function Rustify\{ok, err, some, none};

$raw = '{"name":"James"}';

$res = Json::decodeObject($raw);

if ($res->isErr()) {
    $err = $res->unwrapErr();
    // handle error
} else {
    $data = $res->unwrap();
    // handle data
}
```

### Construct Options
```php
use Rustify\Option;
use function Rustify\{some, none};

$optA = some("value");
$optB = none();

if ($optA->isSome()) {
    $v = $optA->unwrap();
}
```
### Functional transforms:
```php
$nameOpt = some("James")
    ->map(fn($name) => strtoupper($name))
    ->andThen(fn($name) => strlen($name) > 0 ? some($name) : none());

$final = $nameOpt->unwrapOr("Unknown");

```


### Construct Results
```php
use Rustify\Result;
use function Rustify\{ok, err};

function open_file(string $path): Result
{
    return is_readable($path)
        ? ok(file_get_contents($path))
        : err("File not readable");
}

$res = open_file("config.json");

if ($res->isOk()) {
    $content = $res->unwrap();
} else {
    $error = $res->unwrapErr();
}
```

### Chaining:
```php
$parsed = open_file("config.json")
    ->andThen(fn($s) => ok(json_decode($s, true)))
    ->map(fn($arr) => $arr["name"] ?? "none")
    ->unwrapOr("missing");
```



### Using Option and Result for API validation
```php
function validateUser(array $body): Result
{
    if (!isset($body["email"]) || !is_string($body["email"])) {
    return err("Invalid email");
}
    if (!isset($body["name"]) || !is_string($body["name"])) {
    return err("Invalid name");
}

    return ok([
        "name" => $body["name"],
        "email" => $body["email"],
    ]);
}
```



### Using helpers (if_some, if_ok)
```php
use function Rustify\{if_some, if_ok};

$maybeToken = some("abc123");

if_some($maybeToken, function ($token) {
    error_log("Token = $token");
});

$result = ok(42);

if_ok($result, function ($v) {
    echo "Result: $v";
});

```


### Match-like helpers (option_match, result_match)
```php
use function Rustify\{option_match, result_match};

$opt = none();

$msg = option_match(
    $opt,
    fn($v) => "Some: $v",
    fn()   => "None"
);

$res = err("bad input");

$message = result_match(
    $res,
    fn($v) => "OK: $v",
    fn($e) => "ERR: $e"
);

```


# WHY!?

Null is not a value. It is an absence of information. It carries zero context about why something is missing or what state the system is in.

Option and Result turn absence into structured information.
They convey intent, preserve context, and shorten debugging time.

### Option represents an intentional “maybe”:
    Some(value) → a value is present
    None → a value is intentionally absent

### Result represents the outcome of an operation:
    Ok(value) → the operation succeeded
    Err(error) → the operation failed, with a meaningful reason

### By using Option and Result, the purpose of a function becomes explicit.
#### They eliminate ambiguity, prevent silent failures, and preserve information that null would throw away.
    it’s practical
    it reduces bugs
    it simplifies reasoning
    it improves interfaces
    it prevents hours of debugging
    it’s how you write reliable APIs

### This is not “Functional Programming nonsense”, this is industry standard.
    Swift has Optional<T>
    Kotlin has Nullable<T> with smart handling
    Rust has Option<T> and Result<T,E>
    Haskell, Elm, OCaml all use Maybe/Result
    TypeScript uses | undefined but companies implement Option for safety

### Null introduces silent ambiguity

Null can mean any number of different things, but the language gives you no way to distinguish them.
It could signal an error, missing data, invalid input, or an uninitialized value—yet all of these collapse into the same opaque null.

Example:
```php
function findUser(int $id) {
    if ($id === 1) return ['id'=>1, 'name'=>'James'];
    return null;
}
```

What does null mean???
user not found?..
DB error?..
invalid input?..
developer forgot a return statement?..
exception swallowed somewhere?..

In production debugging, you cannot tell which one happened.

null destroys signal clarity.



### Null forces defensive code everywhere

code you’ve written a thousand times:
```php
$user = findUser($id);
    if ($user === null) {
    // handle maybe-error maybe-not-error?
}
```
Your brain has to constantly do
“Is null okay here, or is null an error?”
This cognitive cost multiplies across the codebase.



### Null often fails far away from where the real problem happened

Consider: `Fatal error: Call to a member function foo() on null`.
The real problem happened in a function 3 layers earlier, but the crash happened way later when dereferencing was attempted.

Option and Result prevent this entirely because they force handling.



### Null causes production bugs that are extremely hard to trace
```php
$total = $invoice['amount'] + $invoice['tax'];
```
If `$invoice` was null, you get a warning or `TypeError`.
But logs don’t tell why it was null.
No context survives and you lose the root cause.

Result fixes this by carrying failure information forward:
```php
return err("Invoice not found");
```
You cannot lose the root cause.



### Option makes “maybe” explicit
When something is optional, the type should say so, not the comments, not the mental model, not tribal knowledge.

Example:
```php
function maybeGetEmail(User $u): ?string

// vs

function maybeGetEmail(User $u): Option<string>
```
### Which one communicates meaning?
    ?string → maybe string, maybe null, maybe error, figure it out yourself
    Option<string> → explicitly either Some(string) or None, handle both



### Result replaces exceptions and null-return error codes with structured information

Exceptions in hot paths suck.
null for errors is painful.
Result gives a middle ground:

$result = doThing();
```php
if ($result->isErr()) {
    return $result->unwrapErr();
}
```
#### This is the same reason Go developers use error returns:
    explicit
    linear
    predictable
    no hidden control flow



### Result makes success and failure equally obvious
```php
return err("Invalid request");
return ok($user);
```
Both outcomes are visible. No ambiguity. No surprises.

### Developers get better autocomplete and static analysis
PHPStan/Psalm can reason about:
```php
Option<User>
Result<User, Error>
```
But they cannot reason about null except “maybe null, maybe not.”

### So with Option/Result:
    fewer runtime errors
    more pre-runtime catches
    more confident refactoring
