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

class StandardPost extends Post
{
    /**
     * @var string
     */
    private $title;

    /**
     * @param string $title
     */
    public function hydrateStandard(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return $this->title;
    }
}
