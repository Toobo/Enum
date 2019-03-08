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
 * @method static Left LEFT(\stdClass|string $data)
 * @method static Right RIGHT(\stdClass|string $data)
 */
class Either extends Enum
{
    public const LEFT = Left::class;
    public const RIGHT = Right::class;

    /**
     * @var \stdClass|null
     */
    private $data;

    public function hydrate(\stdClass $data): void
    {
        $this->data = $data;
    }

    public function data(): ?\stdClass
    {
        return $this->data;
    }

    /**
     * @param Either|Enum $enum
     * @return bool
     */
    public function is(Enum $enum): bool
    {
        return $this->looksLike($enum)
            ?? (get_object_vars($this->data()) === get_object_vars($enum->data()));
    }
}
