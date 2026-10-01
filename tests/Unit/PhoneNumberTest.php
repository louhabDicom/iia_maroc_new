<?php

namespace Tests\Unit;

use App\Services\Otp\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Phone canonicalisation is security-relevant: if two spellings of the same
 * number produce two identifiers, an OTP can be sent to one and verified
 * against the other, or a person can register twice to dodge the hourly cap.
 */
class PhoneNumberTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function equivalentSpellings(): array
    {
        return [
            'national' => ['0612345678', '+212612345678'],
            'spaced national' => ['06 12 34 56 78', '+212612345678'],
            'dotted national' => ['06.12.34.56.78', '+212612345678'],
            'plus spaced' => ['+212 6 12 34 56 78', '+212612345678'],
            '00 prefix' => ['00212612345678', '+212612345678'],
            'landline' => ['0522224422', '+212522224422'],
        ];
    }

    #[DataProvider('equivalentSpellings')]
    public function test_it_canonicalises_to_one_form(string $input, string $expected): void
    {
        $this->assertSame($expected, PhoneNumber::normalise($input));
    }

    public function test_it_keeps_a_foreign_number_intact(): void
    {
        $this->assertSame('+33612345678', PhoneNumber::normalise('+33 6 12 34 56 78'));
    }

    public function test_it_returns_an_empty_string_for_blank_input(): void
    {
        $this->assertSame('', PhoneNumber::normalise('   '));
    }

    public function test_it_rejects_a_number_that_is_too_short(): void
    {
        $this->assertFalse(PhoneNumber::isValid('0612'));
    }

    public function test_it_rejects_a_number_without_a_country_code(): void
    {
        // A bare 5-digit string would otherwise be accepted as a local number.
        $this->assertFalse(PhoneNumber::isValid('12345'));
    }

    public function test_it_rejects_a_number_longer_than_e164_allows(): void
    {
        $this->assertFalse(PhoneNumber::isValid('+1234567890123456'));
    }

    public function test_it_masks_all_but_the_country_code_and_last_four_digits(): void
    {
        $this->assertSame('+212*****5678', PhoneNumber::mask('+212612345678'));
    }

    public function test_masking_a_short_value_does_not_leak_it(): void
    {
        $this->assertSame('+212*', PhoneNumber::mask('+2126'));
    }
}
