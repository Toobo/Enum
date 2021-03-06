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
 * @method static ParamsTest CREATE
 */
class ParamsTest extends Enum
{
    const CREATE = 'test';

    public function hydrate(...$args) // phpcs:ignore
    {
    }

    public function test(Enum $enum): bool
    {
        return $this->sameParamsOf($enum);
    }
}
