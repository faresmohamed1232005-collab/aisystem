<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class PurchaseExpiry
{
    public static function normalize(?string $value): ?string
    {
        $value = trim(self::englishDigits((string) $value));

        if ($value === '') {
            return null;
        }

        if (preg_match('/^(\d{2})(\d{2})$/', $value, $matches)) {
            $month = (int) $matches[1];
            if ($month < 1 || $month > 12) {
                throw new InvalidArgumentException('شهر الصلاحية يجب أن يكون بين 01 و12.');
            }

            return sprintf('20%02d-%02d-01', (int) $matches[2], $month);
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            $value .= '-01';
        }

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        $errors = CarbonImmutable::getLastErrors();

        if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
            throw new InvalidArgumentException('صيغة تاريخ الصلاحية غير صحيحة. استخدم MMYY مثل 0229.');
        }

        return $date->format('Y-m-d');
    }

    public static function display(?string $value): ?string
    {
        $normalized = self::normalize($value);

        return $normalized
            ? CarbonImmutable::createFromFormat('!Y-m-d', $normalized)->format('d/m/Y')
            : null;
    }

    private static function englishDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);
    }
}
