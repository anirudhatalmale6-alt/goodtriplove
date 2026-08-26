<?php

namespace App\Support;

class Format
{
    /**
     * 3 245 678 -> "3,2 M" in French, "3.2M" in English. Uses the active
     * locale's decimal separator so a French page never shows "3.2M".
     */
    public static function compact(int|float|null $value): string
    {
        $value = (int) $value;

        if ($value < 1000) {
            return (string) $value;
        }

        $units = [1_000_000_000 => 'B', 1_000_000 => 'M', 1_000 => 'K'];
        $french = in_array(app()->getLocale(), ['fr', 'pt', 'es', 'it', 'de'], true);

        foreach ($units as $threshold => $suffix) {
            if ($value >= $threshold) {
                $number = $value / $threshold;
                $decimals = $number < 10 ? 1 : 0;

                $formatted = number_format($number, $decimals, $french ? ',' : '.', '');

                return $french
                    ? $formatted.' '.($suffix === 'B' ? 'Md' : $suffix)
                    : $formatted.$suffix;
            }
        }

        return (string) $value;
    }

    public static function number(int|float|null $value): string
    {
        $french = in_array(app()->getLocale(), ['fr', 'pt', 'es', 'it', 'de'], true);

        return number_format((float) $value, 0, ',', $french ? ' ' : ',');
    }
}
