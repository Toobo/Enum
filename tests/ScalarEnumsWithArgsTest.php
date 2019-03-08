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

namespace Toobo\Tests;

use Toobo\Enum;
use Toobo\Tests\Fixtures\Bar;
use Toobo\Tests\Fixtures\LastUpdate;
use Toobo\Tests\Fixtures\ParamsTest;
use Toobo\Tests\Fixtures\User;

class ScalarEnumsWithArgsTest extends \PHPUnit\Framework\TestCase
{

    public function testFailWhenNotPassingArgumentsIfRequired()
    {
        $this->expectException(\ArgumentCountError::class);

        User::ACTIVE();
    }

    public function testInstance()
    {
        static::assertInstanceOf(User::class, User::ACTIVE('Jane'));
        static::assertInstanceOf(User::class, User::NOT_ACTIVE('John'));
        static::assertSame('Jane', User::ACTIVE('Jane')->name());
    }

    public function testIs()
    {
        $jane = User::ACTIVE('Jane');
        $john = User::NOT_ACTIVE('John');
        $tim = User::ACTIVE('Tim');

        static::assertFalse($jane->is(User::ACTIVE('Jane', 12)));

        static::assertFalse($jane->is($john));
        static::assertFalse($jane->is($tim));
        static::assertFalse($john->is($jane));
        static::assertFalse($john->is($tim));
        static::assertFalse($tim->is($jane));
        static::assertFalse($tim->is($john));

        static::assertTrue($jane->is($jane));
        static::assertTrue($john->is($john));
        static::assertTrue($tim->is($tim));

        static::assertTrue(User::ACTIVE('Jane', 12)->is(User::ACTIVE(User::_, User::_)));

        static::assertTrue($jane->is(User::ACTIVE('Jane')));
        static::assertTrue($john->is(User::NOT_ACTIVE('John')));
        static::assertTrue($tim->is(User::ACTIVE('Tim')));

        static::assertFalse($jane->is(User::NOT_ACTIVE('Jane')));
        static::assertFalse($john->is(User::ACTIVE('John')));
        static::assertFalse($tim->is(User::NOT_ACTIVE('Tim')));

        static::assertTrue(User::NOT_ACTIVE('Jane')->is(User::_()));
        static::assertTrue(User::ACTIVE('John')->is(User::_()));
        static::assertTrue(User::_()->is(User::_()));
        static::assertTrue(User::NOT_ACTIVE(User::_)->is(User::_()));
        static::assertTrue(User::ACTIVE(User::_)->is(User::_()));

        static::assertFalse(User::_()->is(Bar::_()));
        static::assertFalse(Bar::_()->is(User::_()));
        static::assertTrue(Enum::_()->is(User::_()));
        static::assertTrue(Enum::_()->is(Bar::_()));
        static::assertTrue(Bar::_()->is(Enum::_()));
        static::assertTrue(User::_()->is(Enum::_()));

        static::assertFalse(User::ACTIVE(Bar::class)->is(Bar::_()));
    }

    public function testIsWithObjectParams()
    {
        $plus1 = new \DateTimeZone('+0100');
        $plus2 = new \DateTimeZone('+0200');

        $date1 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-11-08 23:59:59', $plus1);
        $date2 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-11-09 00:59:59', $plus2);
        $date3 = \DateTime::createFromFormat('Y-m-d H:i:s', '2016-11-08 10:59:59', $plus1);

        LastUpdate::useComparisonByDay();

        static::assertTrue(LastUpdate::FAILED($date1)->is(LastUpdate::FAILED($date1)));
        static::assertTrue(LastUpdate::FAILED($date1)->is(LastUpdate::FAILED($date2)));
        static::assertTrue(LastUpdate::FAILED($date3)->is(LastUpdate::FAILED($date3)));

        LastUpdate::usePreciseComparison();

        static::assertTrue(LastUpdate::FAILED($date1)->is(LastUpdate::FAILED($date1)));
        static::assertTrue(LastUpdate::FAILED($date1)->is(LastUpdate::FAILED($date2)));
        static::assertFalse(LastUpdate::FAILED($date1)->is(LastUpdate::FAILED($date3)));
    }

    public function testSameParamsOf()
    {
        self::assertTrue(ParamsTest::CREATE()->test(ParamsTest::CREATE()));
        self::assertTrue(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE('a', 'b')));
        self::assertTrue(ParamsTest::CREATE(['a', 'b'])->test(ParamsTest::CREATE(['a', 'b'])));

        self::assertFalse(ParamsTest::CREATE()->test(ParamsTest::CREATE('a')));
        self::assertFalse(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE('a', 'C')));
        self::assertFalse(ParamsTest::CREATE(['a', 'b'])->test(ParamsTest::CREATE(['a', 'C'])));

        self::assertTrue(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE(Enum::_, 'b')));
        self::assertTrue(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE('a', Enum::_)));
        self::assertTrue(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE(Enum::_, Enum::_)));
        self::assertTrue(ParamsTest::CREATE(['a', 'b'])->test(ParamsTest::CREATE(Enum::_)));

        self::assertFalse(ParamsTest::CREATE()->test(ParamsTest::CREATE(Enum::_)));
        self::assertFalse(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE(Enum::_, 'c')));
        self::assertFalse(ParamsTest::CREATE('a', 'b')->test(ParamsTest::CREATE('b', Enum::_)));
    }

    public function testVariant()
    {
        static::assertSame(User::ACTIVE, User::ACTIVE(User::_)->variant());
        static::assertSame(User::ACTIVE, User::ACTIVE('Foo')->variant());
        static::assertSame(User::NOT_ACTIVE, User::NOT_ACTIVE('Foo')->variant());
        static::assertNull(User::_()->variant());
        static::assertNull(Enum::_()->variant());
    }

    public function testDescribe()
    {
        $class = User::class;
        static::assertSame("{$class}::ACTIVE(_)", User::ACTIVE(User::_)->describe());
        static::assertSame("{$class}::ACTIVE(string)", User::ACTIVE('Foo')->describe());
        static::assertSame("{$class}::ACTIVE(string, _)", User::ACTIVE('Foo', User::_)->describe());
        static::assertSame("{$class}::ACTIVE(string, int)", User::ACTIVE('Foo', 12)->describe());
        static::assertSame("{$class}::_", User::_()->describe());
        static::assertSame(Enum::class . '::_', Enum::_()->describe());
    }

    public function testMatch()
    {
        $matcher = User::matcher(
            [
                User::ACTIVE,
                function (User $user): string {
                    return $user->name();
                },
            ],
            [
                User::NOT_ACTIVE(User::_),
                function (User $user): string {
                    return $user->name() . ' (not active)';
                },
            ],
            [
                User::_(),
                function (User $user): string {
                    return 'Please wait, ' . $user->name();
                },
            ],
            [
                User::ACTIVE(User::_, User::_),
                function (User $user): string {
                    return $user->name() . ' (' . $user->id() . ')';
                },
            ],
            [
                User::ACTIVE('Tim', User::_),
                function (User $user): string {
                    return 'Hello Tim'  . ' (' . $user->id() . ')';
                },
            ]
        );

        static::assertSame('Jane', $matcher(User::ACTIVE('Jane')));
        static::assertSame('Jane (12)', $matcher(User::ACTIVE('Jane', 12)));
        static::assertSame('Tim', $matcher(User::ACTIVE('Tim')));
        static::assertSame('Hello Tim (12)', $matcher(User::ACTIVE('Tim', 12)));
        static::assertSame('John (not active)', $matcher(User::NOT_ACTIVE('John')));
        static::assertSame('Please wait, Luke', $matcher(User::PENDING('Luke')));
    }

    public function testScopedMatcher()
    {
        $scopedMatcher = User::matcher(
            [
                Enum::_(),
                function (): string {
                    return 'I always match';
                },
            ]
        );

        $globalMatcher = Enum::matcher(
            [
                Enum::_(),
                function (): string {
                    return 'I always match';
                },
            ]
        );

        static::assertNull($scopedMatcher(Bar::TWO()));
        static::assertSame('I always match', $scopedMatcher(User::ACTIVE('X')));
        static::assertSame('I always match', $scopedMatcher(User::NOT_ACTIVE('X')));

        static::assertSame('I always match', $globalMatcher(Bar::TWO()));
        static::assertSame('I always match', $globalMatcher(User::ACTIVE('X')));
        static::assertSame('I always match', $globalMatcher(User::NOT_ACTIVE('X')));
    }

    public function testMatcherWithWildcardArgs()
    {
        $matcher = User::matcher(
            [
                User::ACTIVE(User::_),
                function (User $user): string {
                    return $user->name();
                },
            ],
            [
                User::NOT_ACTIVE(User::_),
                function (User $user): string {
                    return $user->name() . ' (not active)';
                },
            ]
        );

        static::assertSame('John (not active)', $matcher(User::NOT_ACTIVE('John')));
        static::assertSame('Jane', $matcher(User::ACTIVE('Jane')));
    }

    public function testMatcherAndStrictArgs()
    {
        $matcher = User::matcher(
            [
                User::ACTIVE('Jane'),
                function (): string {
                    return 'Jane is good.';
                },
            ],
            [
                User::NOT_ACTIVE('John'),
                function (): string {
                    return 'John is bad.';
                },
            ],
            [
                User::NOT_ACTIVE(User::_),
                function (User $user): string {
                    return $user->name() . ' (not active)';
                },
            ],
            [
                User::_(),
                function (): string {
                    return 'Whatever';
                },
            ]
        );

        static::assertSame('Whatever', $matcher(User::ACTIVE('Tim')));
        static::assertSame('Whatever', $matcher(User::ACTIVE('John')));
        static::assertSame('John is bad.', $matcher(User::NOT_ACTIVE('John')));
        static::assertSame('Tim (not active)', $matcher(User::NOT_ACTIVE('Tim')));
        static::assertSame('Jane is good.', $matcher(User::ACTIVE('Jane')));
    }

    public function testMatcherNothing()
    {
        $matcher = User::matcher(
            [
                User::ACTIVE('Jane'),
                function (): string {
                    return 'Jane is good.';
                },
            ],
            [
                User::NOT_ACTIVE('John'),
                function (): string {
                    return 'John is bad.';
                },
            ]
        );

        static::assertNull($matcher(User::ACTIVE('Tim')));
        static::assertNull($matcher(User::NOT_ACTIVE('Tim')));
        static::assertNull($matcher(User::ACTIVE('John')));
        static::assertNull($matcher(User::NOT_ACTIVE('Jane')));
    }
}
