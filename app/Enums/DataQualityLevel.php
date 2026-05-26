<?php

declare(strict_types=1);

namespace App\Enums;

enum DataQualityLevel: int
{
    case VERY_LOW = 20;
    case LOW = 40;
    case MEDIUM = 60;
    case HIGH = 80;
    case OFFICIAL = 95;

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= 95 => self::OFFICIAL,
            $score >= 80 => self::HIGH,
            $score >= 60 => self::MEDIUM,
            $score >= 40 => self::LOW,
            default => self::VERY_LOW,
        };
    }
}
