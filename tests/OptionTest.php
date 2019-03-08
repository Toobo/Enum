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

use Toobo\Tests\Fixtures\Option;

class OptionTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @param string $thing
     * @return Option
     */
    private function safeJsonDecode(string $thing): Option
    {
        $decoded = json_decode($thing);
        if (json_last_error()) {
            return Option::ERROR(json_last_error_msg());
        }

        return Option::OK($decoded);
    }

    public function testSafeFunction()
    {
        static::assertTrue($this->safeJsonDecode('{[')->isError());
        static::assertSame('bar', $this->safeJsonDecode('{"foo":"bar"}')->unwrap()->foo);
    }

    public function testOptionMap()
    {
        $foo = function (\stdClass $value) : string {
            return $value->foo;
        };

        $this->safeJsonDecode('{[')->map($foo)->isError();

        static::assertTrue($this->safeJsonDecode('{[')->map($foo)->isError());
        static::assertSame('bar', $this->safeJsonDecode('{"foo":"bar"}')->map($foo)->unwrap());
    }

    public function testFallback()
    {
        $default = (object)['foo' => 'meh'];

        static::assertSame('meh', $this->safeJsonDecode('{[')->unwrapOr($default)->foo);
        static::assertSame('bar', $this->safeJsonDecode('{"foo":"bar"}')->unwrapOr($default)->foo);
    }
}
