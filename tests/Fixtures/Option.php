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

namespace Toobo\Tests\Fixtures;

use Toobo\Enum;

/**
 * @method static Error ERROR(\Throwable|string $error)
 * @method static Option OK($data)
 *
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 */
class Option extends Enum
{
    public const ERROR = Error::class;
    public const OK = 'ok';

    /**
     * @var mixed
     */
    protected $wrapped;

    public function hydrate($thing): void
    {
        $this->wrapped = $thing;
    }

    /**
     * @return bool
     */
    final public function isError(): bool
    {
        return $this->wrapped instanceof \Throwable;
    }

    /**
     * @param callable $callable
     * @return Option
     */
    final public function map(callable $callable): Option
    {
        return $this->match(
            [
                Option::ERROR,
                function (Error $error): Error {
                    return $error;
                },
            ],
            [
                Option::_,
                function (Option $option) use ($callable): Option {
                    try {
                        return Option::OK($callable($option->unwrap()));
                    } catch (\Throwable $throwable) {
                        return Option::ERROR($throwable);
                    }
                },
            ]
        );
    }

    /**
     * @return mixed
     */
    final public function unwrap()
    {
        if ($this->isError()) {
            throw $this->wrapped;
        }

        return $this->wrapped;
    }

    /**
     * @param $fallback
     * @return mixed
     */
    final public function unwrapOr($fallback)
    {
        if ($this->isError()) {
            return $fallback;
        }

        return $this->wrapped;
    }
}
