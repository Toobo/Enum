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

class ScalarEnumsTest extends \PHPUnit\Framework\TestCase
{

    public function testFailForNonExistentVariant()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageRegExp('/undefined method/');

        Bar::MEH();
    }

    public function testInstance()
    {
        static::assertInstanceOf(Bar::class, Bar::ONE());
        static::assertInstanceOf(Bar::class, Bar::TWO());
    }

    public function testIs()
    {
        $one = Bar::ONE();
        $two = Bar::TWO();

        static::assertFalse(Bar::ONE()->is(Bar::TWO()));
        static::assertFalse(Bar::TWO()->is(Bar::ONE()));
        static::assertTrue(Bar::TWO()->is(Bar::TWO()));
        static::assertFalse(Bar::TWO()->is($one));
        static::assertTrue(Bar::TWO()->is($two));
        static::assertTrue(Bar::ONE()->is(Bar::ONE()));

        static::assertTrue(Bar::ONE()->is(Bar::_()));
        static::assertTrue(Bar::TWO()->is(Bar::_()));
    }

    public function testAnyOf()
    {
        $one = Bar::ONE();

        static::assertTrue($one->isAnyOf(Bar::ONE(), Bar::TWO()));
        static::assertTrue($one->isAnyOf(Bar::TWO(), $one));
        static::assertFalse($one->isAnyOf(Bar::TWO()));
        static::assertTrue($one->isAnyOf(Bar::ONE()));
    }

    public function testAnyVariant()
    {
        $one = Bar::ONE();

        static::assertTrue($one->isAnyVariant(Bar::ONE, Bar::TWO));
        static::assertFalse($one->isAnyVariant(Bar::TWO));
        static::assertTrue($one->isAnyVariant(Bar::ONE));
    }

    public function testForWildcards()
    {
        static::assertFalse(Bar::ONE()->isWildcard());
        static::assertFalse(Bar::ONE()->isVariantWildcard());
        static::assertFalse(Bar::ONE()->isCatchAllWildcard());

        static::assertTrue(Bar::_()->isWildcard());
        static::assertTrue(Bar::_()->isVariantWildcard());
        static::assertFalse(Bar::_()->isCatchAllWildcard());

        static::assertTrue(Enum::_()->isWildcard());
        static::assertFalse(Enum::_()->isVariantWildcard());
        static::assertTrue(Enum::_()->isCatchAllWildcard());
    }

    public function testMatchFailsIfTooManyArgs()
    {
        $this->expectException(\ArgumentCountError::class);

        Bar::TWO()->match(
            [
                Bar::TWO(),
                function (): int {
                    return 2;
                },
                'I should not be here',
            ]
        );
    }

    public function testMatchFailsIfTooLessArgs()
    {
        $this->expectException(\ArgumentCountError::class);

        Bar::TWO()->match(
            [
                Bar::ONE(),
                function (): int {
                    return 1;
                },
            ],
            [
                Bar::TWO(),
            ]
        );
    }

    public function testMatchFailsIfMissingCallback()
    {
        $this->expectException(\TypeError::class);

        Bar::TWO()->match(
            [
                Bar::ONE(),
                function (): int {
                    return 1;
                },
            ],
            [
                Bar::TWO(),
                'I am not callable',
            ]
        );
    }

    public function testMatchFailsIfMissingEnum()
    {
        $this->expectException(\TypeError::class);

        Bar::TWO()->match(
            [
                Bar::ONE(),
                function (): int {
                    return 1;
                },
            ],
            [
                2,
                function (): int {
                    return 2;
                },
            ]
        );
    }

    public function testMatch()
    {
        $matched = Bar::TWO()->match(
            [
                Bar::ONE(),
                function (): int {
                    return 1;
                },
            ],
            [
                Bar::TWO(),
                function (): int {
                    return 2;
                },
            ]
        );

        static::assertSame(2, $matched);
    }
}
