# Enum

A single-class, [Rust](https://doc.rust-lang.org/1.30.0/book/second-edition/ch06-00-enums.html)-inspired enum implementation for PHP.

---
[![license](https://img.shields.io/packagist/l/toobo/Enum.svg?style=flat-square)](http://opensource.org/licenses/MIT)
[![travis-ci status](https://img.shields.io/travis/Toobo/Enum.svg?style=flat-square)](https://travis-ci.org/Toobo/Enum)
[![codecov.io](https://img.shields.io/codecov/c/github/Toobo/Enum.svg?style=flat-square)](http://codecov.io/github/Toobo/Enum?branch=master)
[![release](https://img.shields.io/github/release/Toobo/Enum.svg?style=flat-square)](https://github.com/Toobo/Enum/releases/latest)
[![packagist](https://img.shields.io/packagist/v/toobo/enum.svg?style=flat-square)](https://packagist.org/packages/toobo/enum)
[![PHP version requirement](https://img.shields.io/packagist/php-v/toobo/enum.svg?style=flat-square)](https://packagist.org/packages/toobo/enum)
---


### Basic Enum

Concrete implementations must define public constants which will be the factory methods for the respective enum variations. Something like this:

```php
use Toobo\Enum;

class PostStatus extends Enum
{
    public const PUBLISH = 'publish';
    public const DRAFT = 'draft';
    public const TRASH = 'trash';
}
```

After that, we can get an instance of the enum via "magic" factory methods like this:

```php
/** @var PostStatus $publish */
$publish = PostStatus::PUBLISH();
```

and we can use the value in type declarations:

```php
interface Post
{
    public function postStatus(): PostStatus;
    public function changeStatusTo(PostStatus $postStatus): Post;
}
```

Because factory methods are implemented via [`__callStatic()`](http://www.php.net/manual/en/language.oop5.overloading.php#object.callstatic) it might be desirable to obtain **IDE auto-complete** via the `@method` annotation. E.g.:

```php
use Toobo\Enum;

/**
 * @method static PostStatus PUBLISH()
 * @method static PostStatus DRAFT()
 * @method static PostStatus TRASH()
 */
class PostStatus extends Enum
{
    public const PUBLISH = 'publish';
    public const DRAFT = 'draft';
    public const TRASH = 'trash';
}
```



### Enum With Parameters

An awesome feature in Rust enum implementation is the possibility to add parameters to the enums.

This library provides a similar feature and creating enums with parameters is as simple as implementing an **`hydrate()` method**.

For example:

```php
use Toobo\Enum;

/**
 * @method static Move LX(int $steps)
 * @method static Move RX(int $steps)
 * @method static Move FW(int $steps)
 * @method static Move BW(int $steps)
 */
class Move extends Enum
{
    public const LX = 'left';
    public const RX = 'right';
    public const FW = 'forward';
    public const BW = 'backward';
    
    public $steps = 0;
    
    public function hydrate(int $steps)
    {
        $this->steps = $steps;
    }
}
```

With such class, we can create instance of `Move` like this:

```php
$moveTenStepsLeft = Move::LX(10);
$moveOneStepForward = Move::FW(1);

assert($moveTenStepsLeft->steps === 10);
assert($moveOneStepForward->steps === 1);
```

The argument passed to the "magic" factory methods is straight passed to `hydrate`, which means that as long as `hydrate` uses argument type declaration it is type-safe.

Sometimes it might be desirable that different enum variations accept different arguments, or that only some of the variations accept arguments.

This can be obtained via **variation-specific `hydrate` methods** that are named appending to `hydrate` the *TitleCase* version of the variation constant name.

E.g. for a variation whose constant is `FOO_BAR` the variation-specific hydrate method should be named `hydrateFooBar`.

The magic method will search for a variation-specific *hydration method* to call, if not found will search for the generic `hydrate` method. If none is found none will be called.

In case some arguments are passed, but no hydration method is found, an exception is raised by the package. In case a method is found it is _always_ called, which means that if it is defined with any mandatory parameter and no argument is passed, an exception will be raised by PHP.



### Enum With Sub-classes

The last and most advanced form of enum is the one built using sub-classes.

An example:

```php
use Toobo\Enum;

/**
 * @method static Result OK($thing)
 * @method static Error ERROR(string $message)
 */
class Result extends Enum
{
    public const OK = 'ok';
    public const ERROR = Error::class;
    
    private $wrapped;

    /**
     * Param is optional: `Result::OK()` and `Result::OK($thing)` are both fine.
     */
    public function hydrateOk($thing = null)
    {
        $this->wrapped = $thing;
    }
    
    public function unwrap()
    {
        return $this->wrapped;
    }
    
    public function isError(): bool
    {
        return false;
    }
}
```

And the referenced `Error` class:

```php
final class Error extends Result
{   
    /**
     * Param is required: only `Result::ERROR("Some message")` is allowed.
     */
    public function hydrateError(string $thing)
    {
        $this->wrapped = new \Error($thing)
    }
    
    public function unwrap()
    {
        throw $this->wrapped;
    }
    
    public function isError(): bool
    {
        return true;
    }
}
```

To be noted in the snippets above:

- the value of the constant `Result::ERROR` is the FQN of `Error` class, thats is a sub-class of `Result` class itself.
- the two hydration methods (`Result::hydrateOk()` and `Error::hydrateError()`) have a different signature, and that's the reason we use variation-specific hydration methods
- `Result::OK(...)` will return an instance of `Result`, whereas `Result::ERROR(...)` will return an instance of `Error`, but being it a sub-class of `Result` it will satisfy any type declaration expecting the parent class.

For example we could do something like this:

```php
function safeJsonDecode(string $thing): Result
{
    $decoded = @json_decode($thing);
    if (json_last_error()) {
        return Result::ERROR(json_last_error_msg());
    }

    return Result::OK($decoded);
}
```



### Enum Methods

`Enum` classes (no matter if "basic", with arguments, or with sub-classes) inherit some methods.



#### Getters

There are several getters:

- **`Enum::variant()`**: returns the variation constant _value_, which will be a class FQN in case of sub-classes enums.
- **`Enum::key()`**: returns the variation constant _key_, e.g. for `Result::ERROR` will return the string `"ERROR"`.
- **`Enum::variantClass()`**: returns the variation class FQN, this is the child class FQN in case of sub-classes enums.
- **`Enum::enumClass()`**: returns the enum class FQN, even in case of sub-classes enums, it always return the "parent" enum class.
- **`Enum::describe()`** / **`Enum::__toString()`** The two methods are equivalent. Both return a string representation of the enum, which takes into account the enum class, the variant and (the type of) any argument. For example for an enum created via `Thing::FOO("x", 123)` both these methods would return `"Thing::FOO(string, int)"`; whereas for an enum created via `Thing::BAR()` the returned value would be `"Thing::BAR"`.



#### Checking Enum Identity

**`Enum::isVariant()`** accepts a string and returns true if it matches the current enum variant. Basically, `$enum->isVariant(Thing::FOO)` is the same as `$enum->variant() === Thing::FOO`.

**`Enum::isAnyVariant()`** is the variadic version of `Enum::isVariant()` and arguments are combined with an `OR` logic, i.e. calling `$this->isAnyVariant($a, $b, $c)` is the same as calling: `$this->isVariant($a) || $this->isVariant($b) || $this->isVariant($c)`.

**`Enum::is()`** accepts as argument an enum instance and returns a boolean that is true when the given enum and the enum the method is called on, are equivalent, which means they represent the same "variant" and, in case the enum accepts arguments, they also have same arguments.

```php
assert( Move::LX(10)->is(Move::LX(10)) );
assert( ! Move::LX(10)->is(Move::LX(5)) );
assert( ! Move::LX(10)->is(Move::RX(10)) );
```

There's also **`Enum::isAnyOf()`** which is the variadic version of `Enum::is()`.



##### Custom Identity Check

`Enum::is()` works in the majority of cases as expected, but might be desirable to use a custom logic, especially for Enum with parameters which are objects.

In fact, for scalar or arrays arguments `Enum::is()` does a strict comparison (`===`) , but for objects it does a loose comparison (`==`).

This ensures that "similar" objects with different instances are considered equal. For example, if an enum class takes a `Datetime` object as parameter, and two instances of it took two different instances of  `Datetime` representing the same point in time, the two enums will be considered matching by `Enum::is()`.

However, might be desirable that such instances are considered matching if the _day_ is the same in both of them, ignoring the time. This could be obtained by overriding `Enum::is()` with something like this:

```php
public function is(Enum $enum): bool
{
    $areSame = $this->looksLike($enum);
    if ($areSame !== null) {
        return $areSame;
    }

    // Make sure we compare date in the same timezone
    $enumDate = $enum->date->setTimezone($this->date->getTimezone());

    return $enumDate->format('Ymd') === $this->date->format('Ymd');
}
```

We are using **`Enum::looksLike()`** to check that the two enums are "similar": they share the same Enum concrete class and either they also share same variation or any of them is a wildcard (more on this below).

`Enum::looksLike()` is a `protected` method that has a return type of `bool|null`: when it is capable to determine _for sure_ if the two enums match without looking at construction arguments, it returns either `true` or `false`, when to determine identity is necessary to parse construction arguments it returns `null`.

In this latter case, the method shown above compares dates by day, as per requirement.



##### Wildcard Enum and Wildcard Parameter

`Enum` provides a "wildcard" static method `_()` that can be used to create an instance that is considered equivalent to any variant:

```php
assert( Move::LX(10)->is(Move::_()) );
assert( Move::RX(5)->is(Move::_()) );
assert( Move::_()->is(Move::FW(1)) );
```

Calling the wildcard method directly on `Enum`, instead of on a sub-class, will create an instance that will match *any* enum, because `Enum` is super-class of all of them.

Moreover, `Enum` has a `_` constant that can be used as "wildcard parameter" to be used to construct instances of enums that accept parameters:

```php
assert( Move::LX(10)->is(Move::LX(Enum::_)) );
assert( Move::RX(2)->is(Move::RX(Enum::_)) );
assert( ! Move::RX(2)->is(Move::LX(Enum::_)) );
```

In case the enum takes more parameters, it is possible to use wildcard parameter for only some of them.

```php
assert( Move::LX(10, 5)->is(Move::LX(Enum::_, 5)) );
assert( Move::RX(2, 8)->is(Move::RX(2, Enum::_)) );
assert( ! Move::RX(3, 8)->is(Move::RX(2, Enum::_)) );
```

of course it is possible to use wildcard parameters for all the arguments, which is basically equivalent to use `Enum::isVariant()`:

```php
assert( Move::LX(10, 5)->is(Move::LX(Enum::_, Enum::_)) );
assert( Move::LX(10, 5)->isVariant(Move::LX) );
```

It worth noting that some getters method behave differently for wildcard enums:

- `Enum::variant()` returns null
- `Enum::enumClass()` returns null
- `Enum::key()` returns `"_"`
- `Enum::describe()` returns the name of the class followed by `"::_"`

Finally, when `Enum::describe()` is called on a regular (non-wildcard) instance that makes use of some wildcard arguments, instead of the parameter type, the string `"_"` is used.

```php
assert( Move::_()->variant() === null );
assert( Move::_()->enumClass() === null );
assert( Move::_()->key() === '_' );
assert( Move::_()->describe() === 'Move::_' );
assert( Move::LX(Enum::_)->describe() === 'Move::LX(_)' );
```



### Pattern Matching (sort of)

In Rust, the most powerful and idiomatic way to deal with enums is pattern matching.

In PHP we don't have anything like that, but this library provides a **`Enum::match()`** method that enables something *similar* (unless we look at performance :D).

Let's take as example an enum:

```php
use Toobo\Enum;

/**
 * @method static User ACTIVE(int $id, string $name = 'unknown')
 * @method static User NOT_ACTIVE(int $id, string $name = 'unknown')
 */
final class User extends Enum
{
    public const ACTIVE = 'active';
    public const NOT_ACTIVE = 'not-active';
    
    public $id = -1;
    public $name = 'N/D';
    
    public function hydrate(int $id, string $name = 'N/D')
    {
        $this->id = $id;
        $this->name = $name;
    }
}
```

we can do something like this:

```php
function greet(User $user)
{
    $user->match(
        [User::ACTIVE, function (User $user) {
            print "Welcome back {$user->name}!";
        }],
        [User::NOT_ACTIVE, function (User $user) {
            print "Hi {$user->name}, please activate your account.";
        }],
        [User::ACTIVE(User::_, 'root'), function () {
            print 'Hello Administrator!';
        }],
    );
}

greet(User::ACTIVE(2, 'Jane'));
// "Welcome back Jane!"

greet(User::NOT_ACTIVE(5, 'John'));
// "Hi John, please activate your account."

greet(User::ACTIVE(123, 'root'));
// "Hello Administrator!"
```

**`Enum::match()` accepts a variadic number of 2-items arrays, where the first item is either an enum instance or a string representing an enum variation and the second item is a callable.**

Every first item is compared with `is()` (if it's an instance) or `isVariant()` (if it's a string) with the instance the method is called on, and in case of a match, the value returned by the related callback (2nd item) is immediately returned. The callback will receive as only argument the enum on which the method is called.

`Enum::match()` basically allows for logic-less `switch` implementations.

It is especially useful when the matching *branch* will make use of the enum instance, thanks to the fact that matching callback receives the enum as a parameter.

Another interesting feature is the possibility to use wildcard parameter to implicitly apply matching logic, without any `if`.

For example, in the snippet above, the "administrator" user is matched by requiring a specific user name (`"root"`), ignoring the user id.

For a comparison, an equivalent code would be:

```php
function greet(User $user)
{
    switch (true) {
        case $user->isVariant(User::NOT_ACTIVE)):
            print "Hi {$user->name}, please activate your account.";
            break;
        case $user->is(User::ACTIVE(User::_, 'root')):
            print 'Hello Administrator!';
            break;
        default:
            print "Hi {$user->name}, please activate your account.";
    }
}
```

Which is slightly less code (13 lines VS 14) but it has an higher cyclomatic complexity which will increase linearly with more variants (enum in this example just have two) whereas usage of `match` keep the cyclomatic complexity fixed at 1 no matter the number of variants.

Moreover, we used closures in the `match` example above, but using defined functions/methods/invokable it would be *less* code than a `switch` and more flexible too. E.g.:

```php
/**
 * @var Response|Enum<string, int> $response
 */
function dispatch(Response $response)
{
    $response->match(
        [Response::REDIRECT, new Handler\Redirect()],
        [Response::ERROR, new Handler\Error()],
        [Response::SUCCESS, new Handler\Success()],
        [Response::ERROR(Response::_, 404), new Handler\NotFoundError()],
    );
}
```

Each invokable objects `__invoke()` method will receive the `Response` object as parameter and they can do whatever they want with it, without any need to know from where the object comes from.

But there's more.



#### Matcher Callback

Enum provides an utility method to **encapsulate the matching logic in a callable** object that could be stored and/or passed around. The (static) method **`Enum::matcher()`** accepts as argument the same variadic list of arrays that can be passed to `Enum::match()` and returns a callable object which accepts an enum instance and returns the result of applying the matching logic to it.

```php
/** @var callable $dispatcher */
$dispatcher = Response::matcher(
	$response->match(
        [Response::REDIRECT, new Handler\Redirect()],
        [Response::ERROR, new Handler\Error()],
        [Response::SUCCESS, new Handler\Success()],
        [Response::ERROR(Response::_, 404), new Handler\NotFound()],
    );
);

// $dispatcher($response);
```

The matcher object could be injected into objects or passed as parameter...



#### Matching Pattern Priority

You might have noticed how in snippet above the pattern `Response::ERROR(Response::_, 404)` can match if the string pattern `Response::ERROR` is placed before it in the list, and in theory would "cover" it, matching any error response.

This works because there's a priority being applied when matching patterns. In order:

1. First of all, enum _instances_ without any wildcard parameter are parsed. In case of multiple such instances, order matters;
2. enum *instances* using *wildcard parameters* are matched. These are *ordered by the number of used wildcard parameters*: the less wildcard parameters they use, the more priority they take. In case of multiple such instances with same number of wildcard parameters, order matters;
3. *enum variation constants* are parsed. In case of multiple such instances, order matters;
4. *wildcard enums* (those crated via `_()` method) are parsed. In case of multiple such instances, order matters;
5. Finally, the callback associated with a *catch all* pattern, if found, is executed. There are two ways of define a *catch all* pattern: the *match all* wildcard instance `Enum::_()`, or just the wildcard constant: `Enum::_` . In the case multiple of such *catch all* patterns are present, only the first is executed (if nothing else matches first) and others are ignored.



### Note on Constructors

For Enum classes, `__construct()` can't be called (because `private`) nor can be overridden, because `final`: so the _only clean_ way to obtain enum instances is to call the magic methods.

The reason is that the logic in the rest of the class relies on the `__callStatic` method being called.

It worth also noting that **`hydrate` methods are not constructors** and even if that provides some benefits, (for example all the enum getters are available during hydration) it also brings the issue that extra care should be put on not leaving properties undefined.

For example, in the class:

```php
final class Move extends Enum
{
    public const LX = 'left';
    public const RX = 'right';
    
    private $steps = 0;
    
    public function hydrate(int $steps)
    {
        $this->steps = $steps;
    }
    
    public function steps(): int
    {
        return $this->steps;
    }
}
```

Because there's no constructor, to be sure `$steps` is set, a value is assigned in the declaration (`private $steps = 0`).

In this way, `steps()` method can be safely declared with an `int` type declaration.

An alternative is to don't assign a value in the property declaration and declare the return type of `steps()` as `?int`, dealing in the rest of the class with the fact that `$steps` has a type of `int|null` and not `int`.
