<?php

namespace Tests\Unit;

use App\Support\PurchaseExpiry;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PurchaseExpiryTest extends TestCase
{
    #[DataProvider('validDates')]
    public function test_it_normalizes_supported_expiry_values(string $input, string $expected): void
    {
        $this->assertSame($expected, PurchaseExpiry::normalize($input));
    }

    public static function validDates(): array
    {
        return [
            ['0229', '2029-02-01'],
            ['٠٢٢٩', '2029-02-01'],
            ['۱۲۲۹', '2029-12-01'],
            ['2029-02', '2029-02-01'],
            ['2029-02-17', '2029-02-17'],
        ];
    }

    #[DataProvider('invalidDates')]
    public function test_it_rejects_invalid_expiry_values(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);

        PurchaseExpiry::normalize($input);
    }

    public static function invalidDates(): array
    {
        return [['0029'], ['1329'], ['229'], ['02A9'], ['2029-02-30']];
    }

    public function test_it_displays_the_full_date(): void
    {
        $this->assertSame('01/02/2029', PurchaseExpiry::display('0229'));
    }
}
