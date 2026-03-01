<?php

declare(strict_types=1);

namespace Helpers;

// formatting helpers

class Format
{
    // currency format
    public static function currency(float|string $amount, string $currency = 'USD'): string
    {
        $amount = (float) $amount;
        $symbols = [
            'USD' => '$', 'EUR' => 'E', 'GBP' => 'P',
        ];
        $symbol = $symbols[$currency] ?? $currency . ' ';
        return $symbol . number_format($amount, 2);
    }

    // date format
    public static function date(string $datetime, string $format = 'M d, Y'): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        return date($format, $ts);
    }

    // date + time
    public static function dateTime(string $datetime, string $format = 'M d, Y h:i A'): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }
        return date($format, $ts);
    }

    // time ago string
    public static function timeAgo(string $datetime): string
    {
        $ts = strtotime($datetime);
        if ($ts === false) {
            return $datetime;
        }

        $diff = time() - $ts;

        if ($diff < 0) {
            return 'just now';
        }

        $intervals = [
            ['year', 31536000],
            ['month', 2592000],
            ['week', 604800],
            ['day', 86400],
            ['hour', 3600],
            ['minute', 60],
        ];

        foreach ($intervals as [$label, $seconds]) {
            $count = (int) floor($diff / $seconds);
            if ($count >= 1) {
                $plural = $count === 1 ? '' : 's';
                return "{$count} {$label}{$plural} ago";
            }
        }

        return 'just now';
    }

    // truncate
    public static function truncate(string $text, int $length = 100): string
    {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . '...';
    }
}
