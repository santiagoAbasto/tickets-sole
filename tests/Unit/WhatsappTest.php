<?php

namespace Tests\Unit;

use App\Support\Whatsapp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class WhatsappTest extends TestCase
{
    #[DataProvider('argentineNumbers')]
    public function test_normalizes_argentine_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, Whatsapp::normalize($input, '54'));
    }

    public static function argentineNumbers(): array
    {
        return [
            'plain area + number' => ['11 1234-5678', '5491112345678'],
            'already international with +' => ['+54 9 11 1234 5678', '5491112345678'],
            'already international digits' => ['5491112345678', '5491112345678'],
            'local mobile with trunk 0 and 15' => ['011 15 1234-5678', '5491112345678'],
            'cordoba local with 15' => ['0351 15 678-1234', '5493516781234'],
        ];
    }

    public function test_keeps_other_country_codes_when_international(): void
    {
        $this->assertSame('14155551234', Whatsapp::normalize('+1 415 555 1234', '54'));
        $this->assertSame('59899123456', Whatsapp::normalize('00598 99 123 456', '54'));
    }

    public function test_returns_null_for_invalid_or_empty(): void
    {
        $this->assertNull(Whatsapp::normalize('hola', '54'));
        $this->assertNull(Whatsapp::normalize('', '54'));
        $this->assertNull(Whatsapp::normalize(null, '54'));
        $this->assertNull(Whatsapp::normalize('123', '54'));
    }

    public function test_link_encodes_the_message_text(): void
    {
        $url = Whatsapp::link('5491112345678', "Hola *OSL-1*\nseguimiento");

        $this->assertStringStartsWith('https://wa.me/5491112345678?text=', $url);
        $this->assertStringContainsString('%2A', $url); // "*"
        $this->assertStringContainsString('%0A', $url); // newline
        $this->assertStringContainsString('Hola', $url);
    }
}
