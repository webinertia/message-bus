# PHP semantics (V2)

These facts were executed and observed on **PHP 8.4.24** during this planning session, not
inferred. The project's `composer.json` requires `php: ~8.4.1 || ~8.5.0`, so this is the
target runtime. The full log lives in the V1
[verification file](../resolver-strategy-collaborator/php-semantics-verification.md); the
decisions-relevant subset is reproduced here so V2 stands alone.

## Dynamic method access

```php
$o->{$name}($arg);      // call method $name now
$o->{$name}(...);       // first-class callable: capture as Closure, do NOT call
$o->{$name};            // dynamic PROPERTY read, not a call
```

- `$o->{$name}` with no parentheses reads a property. Observed: `Undefined property` warning
  plus `null`; on a `final readonly` object the same read still warns and returns `null`
  (writes would be `Error: Cannot create dynamic property ...`).
- Therefore the proposal's literal `->{$strategy->handlerMethod($message)};` is property access. The
  intended behavior needs `($message)` or `(...)`.

## First Class Callable Syntax (PHP 8.1+)

- `$obj->{$method}(...)` returns a `Closure` bound to `$obj`.
- Capturing an undefined method with no `__call` throws immediately:
  `Error: Call to undefined method ...`.
- Capturing a name handled by `__call` succeeds; invoking the closure routes through
  `__call`.

## `__call(string $methodName, array $args): mixed`

- `__call` fires only for methods not defined (or inaccessible) on the object. A real method
  always wins over `__call`.
- Parameter list is enforced: parameter `#1` must be `string`. A non-string first parameter
  is a compile-time fatal error.
- Return type is **not** forced to `mixed`. A narrower declaration is legal and enforced at
  runtime under `strict_types=1`:
  - `__call(...): string { return 123; }` → `TypeError: ... Return value must be of type string`.
  - Reflection reports the declared return type.
- Both `__call` and `__invoke` can be declared in an interface in PHP 8.4, and implemented
  by a class. They can therefore be typed contracts, contrary to the common claim.

## `is_callable` vs `method_exists`

With only `__call` present and no real `anything()` method:

```php
method_exists($h, 'anything');   // false
is_callable([$h, 'anything']);   // true
```

V2's middleware guard uses `is_callable`, because it must accept both real methods and
`__call`-backed handlers. The asymmetry also means `is_callable` is a weaker guarantee than
"a typed method exists", it is a callability check, not a signature check.

## Runtime error modes

| Situation | Thrown |
| --- | --- |
| Call to undefined method, no `__call` | `Error: Call to undefined method Class::name()` |
| First-class capture of undefined method, no `__call` | same `Error` |
| Wrong argument count | `ArgumentCountError` |
| Argument narrower than the declared parameter | `TypeError` |
| `__call` returns a value violating its declared return type | `TypeError` |
| Method returns a non-result where `ResultInterface` is required | `TypeError` |
