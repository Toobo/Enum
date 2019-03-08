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
 * @method static LastUpdate SUCCEEDED(\DateTimeInterface $date)
 * @method static LastUpdate FAILED(\DateTimeInterface $date)
 */
final class LastUpdate extends Enum
{
    public const SUCCEEDED = 'succeeded';
    public const FAILED = 'failed';

    private static $comparison = 'day';

    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @return void
     */
    public static function useComparisonByDay(): void
    {
        self::$comparison = 'day';
    }

    /**
     * @return void
     */
    public static function usePreciseComparison(): void
    {
        self::$comparison = 'precise';
    }

    /**
     * @param \DateTime $date
     */
    public function hydrate(\DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @param LastUpdate|Enum $enum
     * @return bool
     */
    public function is(Enum $enum): bool
    {
        if (self::$comparison !== 'day') {
            return parent::is($enum);
        }

        $areSame = $this->looksLike($enum);
        if ($areSame !== null) {
            return $areSame;
        }

        // Make sure we compare date in the same timezone
        /** @var \DateTime $enumDate */
        $enumDate = $enum->date->setTimezone($this->date->getTimezone());

        return $enumDate->format('Ymd') === $this->date->format('Ymd');
    }
}
