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

class ProductPost extends Post
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var int
     */
    private $price;

    /**
     * @param string $title
     * @param int $price
     */
    public function hydrateProduct(string $title, int $price)
    {
        $this->title = $title;
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function title(): ?string
    {
        return $this->title ?? null;
    }

    /**
     * @return int
     */
    public function price(): ?int
    {
        return $this->price ?? null;
    }
}
