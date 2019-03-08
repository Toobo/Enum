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

/**
 * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
 * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration
 */
final class Error extends Option
{
    /**
     * @param string|\Throwable $thing
     */
    public function hydrateError($thing): void
    {
        if (is_string($thing)) {
            $this->wrapped = new \Error($thing);

            return;
        }

        if ($thing instanceof \Throwable) {
            $this->wrapped = new \Error($thing->getMessage(), $thing->getCode(), $thing);

            return;
        }

        $this->wrapped = new \Error();
    }
}
