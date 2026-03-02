<?php

declare(strict_types=1);

namespace App\Util;

use DateTimeImmutable;
use DateTimeInterface;

final class FrenchDateFormatter
{
    /**
     * @var array<string, string>
     */
    private const EN_TO_FR = [
        'Monday' => 'Lundi',
        'Tuesday' => 'Mardi',
        'Wednesday' => 'Mercredi',
        'Thursday' => 'Jeudi',
        'Friday' => 'Vendredi',
        'Saturday' => 'Samedi',
        'Sunday' => 'Dimanche',
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mer',
        'Thu' => 'Jeu',
        'Fri' => 'Ven',
        'Sat' => 'Sam',
        'Sun' => 'Dim',
        'January' => 'Janvier',
        'February' => 'Fevrier',
        'March' => 'Mars',
        'April' => 'Avril',
        'May' => 'Mai',
        'June' => 'Juin',
        'July' => 'Juillet',
        'August' => 'Aout',
        'September' => 'Septembre',
        'October' => 'Octobre',
        'November' => 'Novembre',
        'December' => 'Decembre',
        'Jan' => 'Jan',
        'Feb' => 'Fev',
        'Apr' => 'Avr',
        'Aug' => 'Aou',
        'Sep' => 'Sep',
        'Oct' => 'Oct',
        'Nov' => 'Nov',
        'Dec' => 'Dec',
    ];

    public static function format(mixed $value, string $format = 'l d F'): string
    {
        $date = self::normalizeDate($value);
        if ($date === null) {
            return '';
        }

        return strtr($date->format($format), self::EN_TO_FR);
    }

    private static function normalizeDate(mixed $value): ?DateTimeInterface
    {
        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        if (is_int($value)) {
            return (new DateTimeImmutable())->setTimestamp($value);
        }

        if (is_string($value) && trim($value) !== '') {
            $timestamp = strtotime($value);
            if ($timestamp === false) {
                return null;
            }

            return (new DateTimeImmutable())->setTimestamp($timestamp);
        }

        return null;
    }
}
