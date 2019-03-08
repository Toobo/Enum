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
 * @method static User NOT_ACTIVE(string $name, int|string $id = null)
 * @method static User ACTIVE(string $name, int|string $id = null)
 * @method static User PENDING(string $name, int|string $id = null)
 */
class User extends Enum
{
    const NOT_ACTIVE = 'not-active';
    const ACTIVE = 'active';
    const PENDING = 'pending';

    /**
     * @var string
     */
    private $name;

    /**
     * @var int|null
     */
    private $id;

    /**
     * @param string $title
     * @param int|null $id
     */
    public function hydrate(string $title, int $id = null)
    {
        $this->name = $title;
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->id;
    }
}
