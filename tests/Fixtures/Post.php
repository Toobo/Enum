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
 * @method static StandardPost STANDARD(string $title)
 * @method static ProductPost PRODUCT(string $title, int|string $price = null)
 */
class Post extends Enum
{
    const STANDARD = StandardPost::class;
    const PRODUCT = ProductPost::class;
}
