<?php

namespace Tests\Unit;

use App\Support\ArabicTextShaper;
use PHPUnit\Framework\TestCase;

class ArabicTextShaperTest extends TestCase
{
    public function test_returns_empty_string_for_null_or_empty(): void
    {
        $this->assertSame('', ArabicTextShaper::shapeForPdf(null));
        $this->assertSame('', ArabicTextShaper::shapeForPdf(''));
    }

    public function test_leaves_pure_latin_text_untouched(): void
    {
        $text = 'Menunjukkan penguasaan yang baik dalam Menganalisa tentang perbedaan antara Ilmu Fiqih dan Ilmu Usul Fiqih.';
        $this->assertSame($text, ArabicTextShaper::shapeForPdf($text));
    }

    public function test_shapes_and_reverses_arabic_run_inside_indonesian_sentence(): void
    {
        $input = 'Membaca kosa kata dengan tema التحيات والتعارف.';
        $shaped = ArabicTextShaper::shapeForPdf($input);

        $this->assertStringStartsWith('Membaca kosa kata dengan tema ', $shaped);
        $this->assertStringEndsWith('.', $shaped);
        $this->assertDoesNotMatchRegularExpression('/[\x{0621}-\x{064A}]/u', $shaped);
        $expectedReversed = "\u{FED1}\u{FEAD}\u{FE8E}\u{FECC}\u{FE98}\u{FEDF}\u{FE8D}\u{FEED} \u{FE95}\u{FE8E}\u{FEF4}\u{FEA4}\u{FE98}\u{FEDF}\u{FE8D}";
        $this->assertSame('Membaca kosa kata dengan tema '.$expectedReversed.'.', $shaped);
    }

    public function test_applies_lam_alef_ligature(): void
    {
        // لا -> isolated U+FEFB; السلام -> contains final lam-alef U+FEFC
        $this->assertSame("\u{FEFB}", ArabicTextShaper::shapeForPdf('لا'));

        $salam = ArabicTextShaper::shapeForPdf('السلام');
        $this->assertStringContainsString("\u{FEFC}", $salam);
    }

    public function test_preserves_harakat_attached_to_base_character(): void
    {
        $shaped = ArabicTextShaper::shapeForPdf('بِسْمِ');
        $chars = preg_split('//u', $shaped, -1, PREG_SPLIT_NO_EMPTY);

        // First visual glyph is مِ (final meem U+FEE2 followed by kasra U+0650)
        $this->assertSame("\u{FEE2}", $chars[0]);
        $this->assertSame("\u{0650}", $chars[1]);
    }
}
