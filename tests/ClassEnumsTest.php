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
use Toobo\Tests\Fixtures\Either;
use Toobo\Tests\Fixtures\Left;
use Toobo\Tests\Fixtures\Post;
use Toobo\Tests\Fixtures\Right;
use Toobo\Tests\Fixtures\StandardPost;
use Toobo\Tests\Fixtures\ProductPost;

class ClassEnumsTest extends \PHPUnit\Framework\TestCase
{

    public function testFailWhenNotPassingArgumentsIfRequired()
    {
        $this->expectException(\ArgumentCountError::class);

        Post::PRODUCT('foo');
    }

    public function testFailWhenConstructorCalledOnVariant()
    {
        $this->expectException(\BadMethodCallException::class);

        StandardPost::PRODUCT('foo');
    }

    public function testInstance()
    {
        static::assertInstanceOf(StandardPost::class, Post::STANDARD('Foo'));
        static::assertInstanceOf(ProductPost::class, Post::PRODUCT('Bar', 100));
        static::assertSame(100, Post::PRODUCT('Bar', 100)->price());
    }

    public function testIs()
    {
        static::assertFalse(Post::PRODUCT('Bar', 100)->is(Post::PRODUCT('Bar', 101)));
        static::assertFalse(Post::PRODUCT('Bar', 100)->is(Post::PRODUCT('Bar!', 100)));
        static::assertFalse(Post::PRODUCT('Bar', 100)->is(Post::STANDARD('Bar')));
        static::assertTrue(Post::PRODUCT('Bar', 100)->is(Post::PRODUCT('Bar', 100)));
        static::assertTrue(Post::PRODUCT('Bar', 100)->is(Post::PRODUCT(Post::_, 100)));
        static::assertTrue(Post::PRODUCT('Bar', 100)->is(Post::PRODUCT('Bar', Post::_)));

        static::assertTrue(Post::PRODUCT('Bar', 100)->is(ProductPost::_()));
        static::assertFalse(Post::PRODUCT('Bar', 100)->is(StandardPost::_()));
        static::assertTrue(ProductPost::_()->is(Post::PRODUCT('Bar', 100)));
        static::assertFalse(StandardPost::_()->is(Post::PRODUCT('Bar', 100)));

        static::assertTrue(Post::PRODUCT('Bar', 100)->is(Post::_()));
        static::assertTrue(Post::STANDARD('Bar')->is(Post::_()));
        static::assertTrue(Post::_()->is(Post::PRODUCT('Bar', 100)));
        static::assertTrue(Post::_()->is(Post::STANDARD('Bar')));
    }

    public function testIsCustom()
    {
        $left = Either::LEFT((object)['id' => 1]);

        static::assertFalse($left->is(Either::LEFT((object)['id' => 20])));
        static::assertFalse($left->is(Either::RIGHT((object)['id' => 1])));
        static::assertTrue($left->is($left));
        static::assertTrue($left->is(Either::LEFT((object)['id' => 1])));
        static::assertFalse($left->is(Either::LEFT((object)['id' => 0])));

        static::assertTrue($left->is(Either::LEFT(Either::_)));
        static::assertTrue(Either::LEFT(Either::_)->is($left));

        static::assertTrue($left->is(Either::_()));
        static::assertTrue(Either::_()->is($left));
    }

    public function testGetters()
    {
        $standard = Post::STANDARD('Bar');

        static::assertSame($standard->key(), 'STANDARD');
        static::assertSame($standard->variant(), Post::STANDARD);
        static::assertSame($standard->variantClass(), StandardPost::class);
        static::assertSame($standard->enumClass(), Post::class);
        static::assertSame($standard->describe(), Post::class .'::STANDARD(string)');
        static::assertSame((string)$standard, Post::class .'::STANDARD(string)');

        $post = Post::_();

        static::assertSame($post->key(), '_');
        static::assertSame($post->variant(), null);
        static::assertSame($post->variantClass(), Post::class);
        static::assertSame($post->enumClass(), null);
        static::assertSame($post->describe(), Post::class . '::_');
        static::assertSame((string)$post, Post::class . '::_');

        $enum = Enum::_();

        static::assertSame($enum->key(), '_');
        static::assertSame($enum->variant(), null);
        static::assertSame($enum->variantClass(), Enum::class);
        static::assertSame($enum->enumClass(), null);
        static::assertSame($enum->describe(), Enum::class . '::_');
        static::assertSame((string)$enum, Enum::class . '::_');
    }

    public function testMatcher()
    {
        $matcher = Either::matcher(
            [
               Either::LEFT(Either::_),
                function (Left $left): string {
                    return sprintf("Turn left by %d", $left->data()->x);
                },
            ],
            [
                Either::RIGHT(Either::_),
                function (Right $left): string {
                    return sprintf("Turn right by %d", $left->data()->x);
                },
            ]
        );

        static::assertSame('Turn left by 1', $matcher(Either::LEFT((object)['x' => 1])));
        static::assertSame('Turn right by 10', $matcher(Either::RIGHT((object)['x' => 10])));
    }
}
