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

use Toobo\Tests\Fixtures\Vary;
use Toobo\Tests\Fixtures\Sub;

class VaryEnumsTest extends \PHPUnit\Framework\TestCase
{

    public function testFailWhenPassingArgumentsAndNotAllowedInSubClass()
    {
        $this->expectException(\BadMethodCallException::class);

        Vary::SUB('');
    }

    public function testFailWhenNotPassingArgumentsAndRequired()
    {
        $this->expectException(\ArgumentCountError::class);

        Vary::ARGS();
    }

    public function testInstance()
    {
        static::assertInstanceOf(Sub::class, Vary::SUB());
        static::assertInstanceOf(Vary::class, Vary::SIMPLE());
        static::assertInstanceOf(Vary::class, Vary::ARGS('Foo'));
        static::assertNull(Vary::SIMPLE()->arg());
        static::assertSame('Foo', Vary::ARGS('Foo')->arg());
        static::assertSame('I am sub-class enum.', Vary::SUB()->describe());
    }

    public function testIS()
    {
        static::assertFalse(Vary::SIMPLE()->is(Vary::ARGS('')));
        static::assertFalse(Vary::ARGS('')->is(Vary::ARGS(' ')));
        static::assertFalse(Vary::SUB()->is(Vary::ARGS(' ')));

        static::assertTrue(Vary::ARGS('x')->is(Vary::ARGS('x')));
        static::assertTrue(Vary::ARGS('x')->is(Vary::ARGS(Vary::_)));

        static::assertTrue(Vary::ARGS('x')->is(Vary::_()));
        static::assertTrue(Vary::SIMPLE()->is(Vary::_()));
        static::assertTrue(Vary::SUB()->is(Vary::_()));
    }
}
