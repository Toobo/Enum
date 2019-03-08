<?php declare(strict_types=1);
/**
 * This file is part of the toobo/enum library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright Copyright (c) Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace Toobo;

/**
 * Rust-inspired Enum implementation for PHP.
 *
 * Concrete implementations must define public constants which will be the factory methods for
 * the respective variants.
 *
 * For @example
 *
 * ```php
 * final class Either extends Enum
 * {
 *     public const LEFT = 'lx';
 *     public const RIGHT = 'rx';
 * }
 *
 * $left = Either::LEFT();
 * $right = Either::RIGHT();
 *
 * assert(!$left->is($right));
 * assert($left->is(Either::LEFT()));
 * ```
 *
 * Enums can take arguments. To do that, concrete classes must define a constructor.
 * For @example
 *
 * ```php
 * final class Either extends Enum
 * {
 *     public const LEFT = 'lx';
 *     public const RIGHT = 'rx';
 *
 *     private $what = '';
 *
 *     public function hydrate(string $what)
 *     {
 *          $this->what = $what;
 *     }
 * }
 *
 * $leftFoo = Either::LEFT('Foo');
 * $leftBar = Either::LEFT('Bar');
 *
 * assert(!$leftFoo->is($leftBar));
 * assert($leftFoo->is(Either::LEFT('Foo')));
 * ```
 *
 * The class provides a wildcard method (`_()`) to construct enums that match any of the variants.
 * For @example
 *
 * ```php
 * $wildcard = Either::_();
 *
 * assert($wildcard->is(Either::LEFT('Foo')));
 * assert($wildcard->is(Either::LEFT('Bar')));
 * assert($wildcard->is(Either::RIGHT('Bar')));
 * ```
 *
 * The class also provides a wildcard for parameters `Enum::_` that can be used to create enum
 * instances that match other enums by variant ignoring constructor parameters:
 * For @example
 *
 * ```php
 * $allTheLeft = Either::LEFT(Either::_);
 *
 * assert($allTheLeft->is(Either::LEFT('Foo')));
 * assert($allTheLeft->is(Either::LEFT('Bar')));
 * assert(!$allTheLeft->is(Either::RIGHT('Bar')));
 * ```
 *
 * Finally, enums variants can also be represented by sub-classes, which will be different classes
 * extending the concrete "parent" enum. In that case the enum variants must be defined in constants
 * using the FQCN of the variants.
 * For @example
 *
 * ```php
 * final class Left extends Either {}
 * final class Right extends Either {}
 *
 * class Either extends Enum
 * {
 *     public const LEFT = Left::class;
 *     public const RIGHT = Right::class;
 * }
 * ```
 *
 * It worth noting that it is possible to have enums whose variants are represented by a mix of
 * sub-classes and "scalar" variants, either with or without arguments.
 *
 * @package toobo/enum
 * @author Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 */
abstract class Enum
{
    /**
     * Wildcard parameter.
     * Because we want a constant, and can't use expressions in constants, we use a string that
     * is unpredictable and has very little chances to conflict with a real parameter.
     */
    public const _ = "<?= \0\x0B\\₹؁גꡆ≠ザԐὤ𝄣⣻🀁+" . \PHP_VERSION;

    /**
     * @var string|null
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $bound;

    /**
     * @var string|null
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $class;

    /**
     * @var string|null
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $name;

    /**
     * @var string|null
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $variant;

    /**
     * @var array|null
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $args;

    /**
     * @var string|null
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $wildcardClass;

    /**
     * @var int
     * @suppress PhanReadOnlyPrivateProperty
     */
    private $wildcardArgs = 0;

    /**
     * @var string
     */
    private $desc;

    /**
     * Magic method that is used to build factory methods of the variants defined in the enum class.
     */
    final public static function __callStatic(string $name, array $args = []): Enum
    {
        if (!defined("static::{$name}")) {
            throw new \Error(
                sprintf(
                    'Call to undefined method %s::%s().',
                    __CLASS__,
                    $name
                )
            );
        }

        $bound = get_called_class();

        static::assertCalledOnMain($bound, $name);

        $wildcardArgs = 0;
        foreach ($args as $arg) {
            ($arg === self::_) and $wildcardArgs++;
        }

        $variant = constant("static::{$name}");
        $subClass = static::isSubclassVariant($bound, $variant);
        $enumClass = $subClass ? $variant : $bound;

        /** @var Enum $instance */
        $instance = new $enumClass();
        $instance->wildcardArgs = $wildcardArgs;
        $instance->variant = $variant;
        $instance->bound = $bound;
        $instance->class = $enumClass;
        $instance->name = $name;
        $instance->args = $args ?: null;

        return $wildcardArgs ? $instance : $instance->maybeHydrate($args, $name, $bound, $subClass);
    }

    /**
     * Creates a wildcard enum.
     *
     * The created wildcard will match any of the variants of a enum that is a subclass or a super
     * class of the class used to call the wildcard method.
     *
     * For @example
     *
     * ```php
     * $allEither = Either::_();
     *
     * assert(!$allEither->is(Options::_()));
     * assert($allEither->is(Either::LEFT()));
     * assert($allEither->is(Either::RIGHT()));
     * ```
     *
     * Because the class `Enum` is a super-class of all the enums, via `Enum::_()` it is possible
     * to obtain an enum that will match any enum.
     */
    final public static function _(): Enum
    {
        $instance = new class extends Enum
        {
        };
        $instance->wildcardClass = get_called_class();

        return $instance;
    }

    /**
     * Creates a reusable enum matching callback.
     *
     * When using `match()` the logic build using the enum/callback couples is applied to the
     * instance the method is called on, and it is not possible to apply to another enum instance
     * unless enum/callback couples are passed over.
     *
     * This method creates a callable that encapsulates the logic and applies it to any enum
     * that is passed as argument.
     * For @example
     *
     * ```php
     * $matcher = Either::matcher(
     *    [Either::LEFT(), function () { echo "Left"; }],
     *    [Either::RIGHT(), function () { echo "Right"; }],
     * );
     *
     * $matcher(Either::LEFT());  // echoes "Left"
     * $matcher(Either::RIGHT()); // echoes "Right"
     * ```
     *
     * In contrast to calling `match` directly on an enum instance, this methods creates a matcher
     * that is "scoped" on the enum class it is called on.
     * E.g. calling `Either::matcher()` will create a callback that will only match `Either`
     * enum instance, calling it on any other enum will do nothing, even if the matching pattern
     * contains a wildcard lie `Enum::_()`.
     *
     * For @example
     *
     * ```php
     * $allEither = Either::matcher([Enum::_(), function () { echo 'This matches any Either.'; }]);
     *
     * $matcherAll = Enum::matcher([Enum::_(), function () { echo 'This will match any enum.'; }]);
     *
     * $matcherEither(Option::OK('Foo'));  // do nothing
     * $matcherAll(Option::OK('Foo'));     // echoes "This will match any enum."
     * ```
     *
     * @param array{0:string|Enum,1:callable} ...$patterns
     *
     * @see Enum::match()
     */
    final public static function matcher(array ...$patterns): callable
    {
        $class = get_called_class();

        /**
         * @suppress PhanUnreferencedClosure
         * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
         */
        return function (Enum $enum) use ($patterns, $class) {
            // phpcs:enable
            if (!is_a($enum, $class)) {
                return null;
            }

            return $enum->match(...$patterns);
        };
    }

    /**
     * When using sub-class enums, this method ensures that named constructors are called on the
     * "main" enum.
     *
     * Assuming an Enum `Test` with sub-classes variants `Test::A(): A` and `Test::B(): B`, this
     * method ensures that magic `::A()` and `::B()` methods are called on the `Test` instance,
     * preventing things like `A::B()` or `B::A()`, which would be confusing.
     */
    private static function assertCalledOnMain(string $bound, string $name): void
    {
        static $declaring;
        isset($declaring) or $declaring = [];
        isset($declaring[$bound]) or $declaring[$bound] = [];
        if (!isset($declaring[$bound][$name])) {
            $ref = new \ReflectionClassConstant($bound, $name);
            $declaring[$bound][$name] = $ref->getDeclaringClass()->getName();
        }

        if ($declaring[$bound][$name] !== $bound) {
            throw new \BadMethodCallException(
                sprintf(
                    '%1$s::%2$s() is invalid, please use %3$s::%2$s() instead.',
                    $bound,
                    $name,
                    $declaring[$bound][$name]
                )
            );
        }
    }

    /**
     * Given the late-bound FQN, and the target variant, this methods returns true target
     * variant is a subclass of the late-bound class.
     *
     * Memoize result per request.
     */
    private static function isSubclassVariant(string $bound, string $class): bool
    {
        static $variants;
        isset($variants) or $variants = [];
        isset($variants[$bound]) or $variants[$bound] = [];

        if (!isset($variants[$bound][$class])) {
            $variants[$bound][$class] =
                is_string($class)
                && class_exists($class)
                && is_a($class, $bound, true);
        }

        return $variants[$bound][$class];
    }

    /**
     * Avoid enum direct construction.
     */
    final public function __construct()
    {
    }

    /**
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     */
    public function __toString()
    {
        // phpcs:enable

        return $this->describe();
    }

    /**
     * Returns true when given enum is "equivalent" to current instance.
     *
     * Being "equivalent" means that, either:
     * - the two enums share same class and same constructor arguments (if any)
     * - the two enums share same class and constructor parameters are the same or (at least) one of
     *   two enums uses wildcard parameter.
     * - (at least) one of the two enum is a wildcard enum compatible with the other.
     *
     * Besides the constructor, this is the only not final method of the class, allowing for
     * custom implementation for sub-class based enums.
     */
    public function is(Enum $enum): bool
    {
        return $this->looksLike($enum) ?? $this->sameParamsOf($enum);
    }

    /**
     * Predicate that returns true when the given variant name match current instance variant.
     */
    final public function isVariant(string $variant): bool
    {
        return $this->variant === $variant || $variant === self::_ || $this->variant === self::_;
    }

    /**
     * Predicate that returns true when current instance is a wildcard of any type.
     */
    final public function isWildcard(): bool
    {
        return (bool)$this->wildcardClass;
    }

    /**
     * Predicate that returns true when current instance is the "catch all" wildcard: `Enum::_()`.
     */
    final public function isCatchAllWildcard(): bool
    {
        return $this->wildcardClass === Enum::class;
    }

    /**
     * Predicate that returns true when current instance is a wildcard, but not a "catch all" one.
     */
    final public function isVariantWildcard(): bool
    {
        return $this->isWildcard() && !$this->isCatchAllWildcard();
    }

    /**
     * Predicate that returns true when the given enum is either a wildcard enum compatible with
     * current instance, or the two are instances of the same enum variant.
     *
     * @suppress PhanPossiblyNullTypeArgumentInternal
     */
    final public function isSameVariant(Enum $enum): bool
    {
        if ($enum === $this) {
            return true;
        }

        if ($this->wildcardClass || $enum->wildcardClass) {
            return $this->compareAsWildcard($enum);
        }

        if ($this->class === $enum->class
            || is_a($this->class, $enum->class, true)
            || is_a($enum->class, $this->class, true)
        ) {
            return $enum->variant === $this->variant;
        }

        return false;
    }

    /**
     * A variadic version of `is()` where OR logic is used to combine the various result, i.e.
     * `$this->isAnyOf($a, $b)` is the same as `$this->is($a) || $this->is($b)`.
     */
    final public function isAnyOf(Enum ...$enums): bool
    {
        foreach ($enums as $enum) {
            if ($this->is($enum)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A variadic version of `isVariant()` where OR logic is used to combine the various result,
     * i.e. calling `$this->isAnyVariant($a, $b, $c)` is the same as calling:
     * `$this->isVariant($a) || $this->isVariant($b) || $this->isVariant($c)`.
     */
    final public function isAnyVariant(string ...$variants): bool
    {
        foreach ($variants as $variant) {
            if ($this->isVariant($variant)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the variant key string when is not a wildcard instance.
     */
    final public function key(): string
    {
        return $this->wildcardClass ? '_' : ($this->name ?? '');
    }

    /**
     * Returns the variant string or null when a wildcard instance.
     */
    final public function variant(): ?string
    {
        return $this->wildcardClass ? null : $this->variant;
    }

    /**
     * Returns the variant class which could be the "parent" enum or any child enum class FQN.
     */
    final public function variantClass(): string
    {
        return $this->wildcardClass ?? ($this->class ?? '');
    }

    /**
     * Returns the enum class or null when a wildcard instance.
     */
    final public function enumClass(): ?string
    {
        return $this->wildcardClass ? null : $this->bound;
    }

    /**
     * Returns a string describing the enum.
     */
    public function describe(): string
    {
        if ($this->desc) {
            return $this->desc;
        }

        $params = $this->args ?? [];
        $args = '';
        foreach ($params as $param) {
            if ($param === self::_) {
                $args .= $args ? ', _': '_';
                continue;
            }
            $type = is_object($param) ? get_class($param) : gettype($param);
            $type === 'double' and $type = 'float';
            $type === 'integer' and $type = 'int';
            $type === 'boolean' and $type = 'bool';
            $args and $args .= ', ';
            $args .= $type;
        }

        $this->desc = $this->wildcardClass
            ? "{$this->wildcardClass}::_"
            : "{$this->bound}::{$this->name}";
        $args and $this->desc .= "({$args})";

        return $this->desc;
    }

    /**
     * Sort-of pattern matching implementation to apply different logic based on the enum variant
     * of the current instance.
     *
     * The argument is a variadic list of 2-items arrays, where 1st item must be an enum instance
     * or a variant string and 2nd item must be a callable.
     * Given arrays are parsed calling `$this->is()` (or `$this->isVariant()`) with 1st item as
     * argument, and in case of match the related callback is executed (and return value
     * returned) passing the matching enum as only parameter to the callback.
     *
     * The order in which patterns are matches is:
     *   1. enum instances created with params, e.g. `Either::RIGHT(2)`
     *   2. enum instances created with wildcard params, e.g. `Either::RIGHT(Either::_)` and enum
     *      variants as strings, e.g. `Either::RIGHT`
     *   3. enum wildcard instances, e.g. `Either::_()`
     *   4. Fallback ot generic wildcards, e.g. `Enum::_()`, `Enum::_`  (or `Either::_`)
     *
     * For @example
     *
     * ```php
     * // Assuming this enum...
     * final class Either extends Enum
     * {
     *     public const LEFT = 'lx';
     *     public const RIGHT = 'rx';
     *
     *     public $steps = 0;
     *
     *     public function hydrate(int $steps) {
     *          $this->steps = $steps;
     *     }
     * }
     *
     * // We can write this function
     * function where_to_go(Either $either): string
     * {
     *    return $either->match(
     *        [
     *            Either::RIGHT,
     *            function (Either $either): string {
     *                  return sprintf('Turn right for %s steps', $either->steps)
     *            }
     *        ],
     *        [
     *            Either::RIGHT(10),
     *            function (): string {
     *                  return sprintf('Turn right for 10 steps!')
     *            }
     *        ],
     *        [
     *            Either::_,
     *            function (): string {
     *                  return 'This equals to the "default" of a switch';
     *            }
     *        ]
     *    );
     * }
     *
     * assert(where_to_go(Either::RIGHT(2)) === 'Turn right for 2 steps');
     * assert(where_to_go(Either::LEFT(1)) === 'This equals to the "default" of a switch');
     * assert(where_to_go(Either::RIGHT(10)) === 'Turn right for 10 steps!');
     * ```
     *
     * @param array{0:string|Enum,1:callable} ...$patterns
     * @return mixed|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     * phpcs:disable Generic.Metrics.NestingLevel
     */
    final public function match(array ...$patterns)
    {
        // phpcs:enable

        $variants = [];
        $enumVariants = [];
        $wildcards = [];
        /** @var callable|null $anything */
        $anything = null;

        foreach ($patterns as $pattern) {
            $info = $this->matchPatternInfo($pattern);

            $execute = false;
            switch (true) {
                case $info->isEnum:
                    $execute = true;
                    break;
                case $info->isStringVariant:
                    $variants[] = [$info->enum, $info->callback];
                    break;
                case $info->isEnumVariant:
                    $argsNum = $info->wildcardArgs;
                    array_key_exists($argsNum, $enumVariants) or $enumVariants[$argsNum] = [];
                    $enumVariants[$argsNum][] = [$info->enum, $info->callback];
                    break;
                case $info->isWildcard:
                    $wildcards[] = [$info->enum, $info->callback];
                    break;
                case $info->isAnything:
                    $anything or $anything = $info->callback;
                    break;
            }

            // Enum instances with regular (or no) arguments are executed immediately, if they match
            if ($execute && $this->is($info->enum)) {
                return ($info->callback)($this);
            }
        }

        /**
         * If no "regular" enum matched, let's try enum with variadic params, ordered by the number
         * wildcard params they have: the less wildcards, the first they are executed.
         *
         * @var array{0:Enum,1:callable}[][] $enumVariants
         */
        $enumVariants and ksort($enumVariants);
        foreach ($enumVariants as $orderedEnumVariants) {
            /** @var Enum $enumVariant */
            foreach ($orderedEnumVariants as [$enumVariant, $callback]) {
                if ($this->is($enumVariant)) {
                    return $callback($this);
                }
            }
        }

        /**
         * If no enum with variadic params matched, we try to find a match for string variants.
         *
         * @var string|Enum $variant
         * @var callable $callback
         * @var bool $isString
         */
        foreach ($variants as [$variant, $callback]) {
            if ($this->isVariant($variant)) {
                return $callback($this);
            }
        }

        /**
         * If also variants failed, we try to find a match for wildcard instances.
         *
         * @var Enum $wildcard
         * @var callable $callback
         */
        foreach ($wildcards as [$wildcard, $callback]) {
            if ($this->is($wildcard)) {
                return $callback($this);
            }
        }

        /**
         * If nothing else matched, and we have a general fallback, let's execute related callback.
         */
        if ($anything) {
            return $anything($this);
        }

        return null;
    }

    /**
     * Returns a boolean when equivalence between given enum and current instance can be established
     * without fully analyzing constructor parameters.
     *
     * If the two enums share same variant, and both of them use constructor parameters, and none
     * of them use only wildcard parameters, then it is not possible to determine equivalence and
     * the method returns null.
     *
     * This is useful in custom `is()` implementation when comparison of arguments is custom, but
     * variants & wildcard comparison logic can be the default one.
     */
    final protected function looksLike(Enum $enum): ?bool
    {
        if (!$this->isSameVariant($enum)) {
            return false;
        }

        // If looksLike was true and either $this or $enum are wildcard, we can assume equality
        if ($this->wildcardClass || $enum->wildcardClass) {
            return true;
        }

        // @phan-suppress-next-line PhanPossiblyNullTypeArgumentInternal
        $thisArgsCount = $this->args ? count($this->args) : 0;
        // @phan-suppress-next-line PhanPossiblyNullTypeArgumentInternal
        $enumArgsCount = $this->args ? count($enum->args) : 0;

        if ($thisArgsCount === $enumArgsCount
            && ($this->wildcardArgs === $thisArgsCount || $enum->wildcardArgs === $enumArgsCount)
        ) {
            return true;
        }

        return null;
    }

    /**
     * Given an enum returns true either if it was constructed with the same constructor parameters
     * of the current instance, or if one of the two was constructed with wildcard parameters.
     */
    final protected function sameParamsOf(Enum $enum): bool
    {
        // If at least one is null, return true if both ar null.
        if ($this->args === null || $enum->args === null) {
            return $this->args === null && $enum->args === null;
        }

        // Both are arrays, but different length: params are different
        // @phan-suppress-next-line PhanPossiblyNullTypeArgumentInternal
        if (count($this->args) !== count($enum->args)) {
            return false;
        }

        foreach ($this->args as $i => $arg) {
            if ($arg === self::_ || $enum->args[$i] === self::_) {
                continue;
            }

            $same = !is_scalar($arg) && !is_scalar($enum->args[$i])
                ? $arg == $enum->args[$i] // phpcs:ignore
                : $arg === $enum->args[$i];

            if (!$same) {
                return false;
            }
        }

        return true;
    }

    /**
     * Set arguments on the instance via the hydrate method.
     */
    private function maybeHydrate(array $args, string $name, string $bound, bool $subClass): Enum
    {
        static $hydrateMethods;
        isset($hydrateMethods) or $hydrateMethods = [];
        isset($hydrateMethods[$bound]) or $hydrateMethods[$bound] = [];
        if (!isset($hydrateMethods[$bound][$name])) {
            $calledName = str_replace(' ', '', ucwords(str_replace('_', ' ', strtolower($name))));
            $hydrateMethod = "hydrate{$calledName}";
            $toCheck = false;
            if (!is_callable([$this, $hydrateMethod])) {
                $hydrateMethod = 'hydrate';
                $toCheck = true;
            }
            $hydrateMethods[$bound][$name] = [$hydrateMethod, $toCheck, true];
        }

        [$hydrateMethod, $toCheck, $methodExists] = $hydrateMethods[$bound][$name];

        if ($toCheck) {
            $methodExists = is_callable([$this, $hydrateMethod]);
            // When checking for "hydrate" on a subclass, we make sure we check for other subclasses
            $toCheck = $subClass ? $hydrateMethod === 'hydrate' : false;
            $hydrateMethods[$bound][$name][1] = $toCheck;
            $hydrateMethods[$bound][$name][2] = $methodExists;
        }

        if (!$methodExists && $args) {
            $hydrateMethods[$bound][$name][2] = true;
            throw new \BadMethodCallException(
                "{$bound}::{$name}() can't accept arguments hydrate method is not defined."
            );
        }

        if ($methodExists) {
            // @phan-suppress-next-line PhanUndeclaredMethod
            $this->{$hydrateMethod}(...$args);
        }

        return $this;
    }

    /**
     * Given an enum, this method returns a boolean that is true if either the given enum or the
     * current instance are wildcard enum compatible instances.
     *
     * @suppress PhanPossiblyNullTypeArgumentInternal
     */
    private function compareAsWildcard(Enum $enum): bool
    {
        $bothAreWildcard = $this->wildcardClass && $enum->wildcardClass;
        $thisIsWildcard = $this->wildcardClass && $enum->class;
        $enumIsWildcard = $enum->wildcardClass && $this->class;

        return $this->wildcardClass === $enum->wildcardClass
            || ($this->wildcardClass === Enum::class)
            || ($enum->wildcardClass === Enum::class)
            || ($this->class && $this->class === $enum->wildcardClass)
            || ($enum->class && $enum->class === $enum->wildcardClass)
            || ($thisIsWildcard && is_a($this->wildcardClass, $enum->class, true))
            || ($thisIsWildcard && is_a($enum->class, $this->wildcardClass, true))
            || ($enumIsWildcard && is_a($enum->wildcardClass, $this->class, true))
            || ($enumIsWildcard && is_a($this->class, $enum->wildcardClass, true))
            || ($bothAreWildcard && is_a($this->wildcardClass, $enum->wildcardClass, true))
            || ($bothAreWildcard && is_a($enum->wildcardClass, $this->wildcardClass, true));
    }

    /**
     * After checking the validity of given `match` pattern, extract from it information, and
     * returns them wrapped in an object for consuming in `match()` method.
     *
     * @param array{0:string|Enum,1:callable} $pattern
     *
     * @see Enum::match()
     */
    private function matchPatternInfo(array $pattern): \stdClass
    {
        $exception = 'Enum::match() requires a list of 2-items arrays,'
            . ' where first item is an enum or variant string and second item is a callback.';

        if (count($pattern) !== 2) {
            throw new \ArgumentCountError($exception);
        }

        /** @var Enum|string $enum */
        $enum = reset($pattern);
        $callback = end($pattern);
        $isStringVariant = is_string($enum);
        if ((!$enum instanceof Enum && !$isStringVariant) || !is_callable($callback)) {
            throw new \TypeError($exception);
        }

        $data = (object)[
            'isEnum' => false,
            'isEnumVariant' => false,
            'isStringVariant' => false,
            'isWildcard' => false,
            'isAnything' => false,
            'enum' => $enum,
            'callback' => $callback,
        ];

        if ($isStringVariant) {
            $enum === self::_ ? $data->isAnything = true : $data->isStringVariant = true;

            return $data;
        }

        // @phan-suppress-next-line PhanPossiblyNonClassMethodCall
        if ($enum->isCatchAllWildcard()) {
            $data->isAnything = true;

            return $data;
        }

        if ($enum->wildcardClass) {
            $data->isWildcard = true;

            return $data;
        }

        if ($enum->wildcardArgs) {
            $data->isEnumVariant = true;
            $data->wildcardArgs = $enum->wildcardArgs;

            return $data;
        }

        $data->isEnum = true;

        return $data;
    }
}
