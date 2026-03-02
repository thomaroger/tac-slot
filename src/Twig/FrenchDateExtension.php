<?php

declare(strict_types=1);

namespace App\Twig;

use App\Util\FrenchDateFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class FrenchDateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter('fr_date', [$this, 'formatFrenchDate'])];
    }

    public function formatFrenchDate(mixed $value, string $format = 'l d F'): string
    {
        return FrenchDateFormatter::format($value, $format);
    }
}
