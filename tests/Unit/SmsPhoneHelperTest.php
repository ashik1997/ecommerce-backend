<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SmsPhoneHelperTest extends TestCase
{
    /** @dataProvider formattedBangladeshiPhoneProvider */
    public function test_formatted_bangladeshi_phone_is_normalized_before_validation(string $phone): void
    {
        $normalized = normalizeBDPhone($phone);

        $this->assertSame('8801712345678', $normalized);
        $this->assertTrue(validateBDPhone($normalized));
    }

    public function formattedBangladeshiPhoneProvider(): array
    {
        return [
            'local' => ['01712345678'],
            'international' => ['+8801712345678'],
            'with spaces' => ['01712 345 678'],
            'with dashes' => ['01712-345-678'],
        ];
    }
}
