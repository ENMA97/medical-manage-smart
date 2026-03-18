<?php

namespace App\Helpers;

use Carbon\Carbon;
use IntlDateFormatter;

class HijriDate
{
    /**
     * تحويل تاريخ ميلادي إلى هجري باستخدام IntlDateFormatter
     */
    public static function fromGregorian(Carbon|string|null $date = null): string
    {
        $date = $date ? Carbon::parse($date) : now();

        if (!extension_loaded('intl')) {
            // Fallback: approximate conversion
            return self::approximateHijri($date);
        }

        $formatter = new IntlDateFormatter(
            'ar_SA@calendar=islamic-civil',
            IntlDateFormatter::SHORT,
            IntlDateFormatter::NONE,
            'Asia/Riyadh',
            IntlDateFormatter::TRADITIONAL,
            'yyyy/MM/dd'
        );

        $result = $formatter->format($date->getTimestamp());

        return $result ?: self::approximateHijri($date);
    }

    /**
     * تقريب بسيط للتحويل الهجري (احتياطي)
     */
    private static function approximateHijri(Carbon $date): string
    {
        $julianDay = gregoriantojd(
            (int) $date->format('n'),
            (int) $date->format('j'),
            (int) $date->format('Y')
        );

        $hijri = jdtojewish($julianDay); // Not accurate for Hijri but serves as fallback

        // Simple arithmetic approximation
        $year = (int) $date->format('Y');
        $dayOfYear = (int) $date->format('z');

        $hijriYear = (int) floor(($year - 622) * (33.0 / 32.0));
        $hijriMonth = (int) floor(($dayOfYear / 365.0) * 12) + 1;
        $hijriDay = (int) floor(($dayOfYear % 30.4) + 1);

        return sprintf('%04d/%02d/%02d', $hijriYear, min($hijriMonth, 12), min($hijriDay, 30));
    }
}
