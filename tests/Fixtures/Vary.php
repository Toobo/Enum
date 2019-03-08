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
 * @method static Vary SIMPLE()
 * @method static Vary ARGS(string $arg)
 * @method static Sub SUB()
 */
class Vary extends Enum
{
    public const SIMPLE = 'simple';
    public const ARGS = 'args';
    public const SUB = Sub::class;

    /**
     * @var string|null
     */
    private $arg;

    /**
     * @param string $arg
     */
    public function hydrateArgs(string $arg)
    {
        $this->arg = $arg;
    }

    /**
     * @return string|null
     */
    public function arg(): ?string
    {
        return $this->arg;
    }
}
