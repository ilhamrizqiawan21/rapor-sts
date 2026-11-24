<?php

namespace App\Support;

/**
 * Shapes UTF-8 Arabic text into Unicode Presentation Forms-B and reverses RTL runs
 * so DomPDF (which lacks native BiDi/Arabic shaping) renders connected right-to-left Arabic.
 */
class ArabicTextShaper
{
    private const ARABIC_CHAR_PATTERN = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}]/u';

    /**
     * Match an RTL run: starts and ends with an Arabic/RTL character, allowing internal
     * spaces, Arabic punctuation, and digits between Arabic words.
     */
    private const RTL_RUN_PATTERN = '/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}](?:[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\s0-9]*[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}])?/u';

    /**
     * Contextual glyph map: [isolated, final, initial, medial].
     * Null means the character does not have that form (right-joining only).
     *
     * @var array<string, array{0: string, 1: string, 2: ?string, 3: ?string}>
     */
    private const GLYPHS = [
        "\u{0621}" => ["\u{FE80}", "\u{FE80}", null, null],         // ء HAMZA
        "\u{0622}" => ["\u{FE81}", "\u{FE82}", null, null],         // آ ALEF WITH MADDA ABOVE
        "\u{0623}" => ["\u{FE83}", "\u{FE84}", null, null],         // أ ALEF WITH HAMZA ABOVE
        "\u{0624}" => ["\u{FE85}", "\u{FE86}", null, null],         // ؤ WAW WITH HAMZA ABOVE
        "\u{0625}" => ["\u{FE87}", "\u{FE88}", null, null],         // إ ALEF WITH HAMZA BELOW
        "\u{0626}" => ["\u{FE89}", "\u{FE8A}", "\u{FE8B}", "\u{FE8C}"], // ئ YEH WITH HAMZA ABOVE
        "\u{0627}" => ["\u{FE8D}", "\u{FE8E}", null, null],         // ا ALEF
        "\u{0628}" => ["\u{FE8F}", "\u{FE90}", "\u{FE91}", "\u{FE92}"], // ب BEH
        "\u{0629}" => ["\u{FE93}", "\u{FE94}", null, null],         // ة TEH MARBUTA
        "\u{062A}" => ["\u{FE95}", "\u{FE96}", "\u{FE97}", "\u{FE98}"], // ت TEH
        "\u{062B}" => ["\u{FE99}", "\u{FE9A}", "\u{FE9B}", "\u{FE9C}"], // ث THEH
        "\u{062C}" => ["\u{FE9D}", "\u{FE9E}", "\u{FE9F}", "\u{FEA0}"], // ج JEEM
        "\u{062D}" => ["\u{FEA1}", "\u{FEA2}", "\u{FEA3}", "\u{FEA4}"], // ح HAH
        "\u{062E}" => ["\u{FEA5}", "\u{FEA6}", "\u{FEA7}", "\u{FEA8}"], // خ KHAH
        "\u{062F}" => ["\u{FEA9}", "\u{FEAA}", null, null],         // د DAL
        "\u{0630}" => ["\u{FEAB}", "\u{FEAC}", null, null],         // ذ THAL
        "\u{0631}" => ["\u{FEAD}", "\u{FEAE}", null, null],         // ر REH
        "\u{0632}" => ["\u{FEAF}", "\u{FEB0}", null, null],         // ز ZAIN
        "\u{0633}" => ["\u{FEB1}", "\u{FEB2}", "\u{FEB3}", "\u{FEB4}"], // س SEEN
        "\u{0634}" => ["\u{FEB5}", "\u{FEB6}", "\u{FEB7}", "\u{FEB8}"], // ش SHEEN
        "\u{0635}" => ["\u{FEB9}", "\u{FEBA}", "\u{FEBB}", "\u{FEBC}"], // ص SAD
        "\u{0636}" => ["\u{FEBD}", "\u{FEBE}", "\u{FEBF}", "\u{FEC0}"], // ض DAD
        "\u{0637}" => ["\u{FEC1}", "\u{FEC2}", "\u{FEC3}", "\u{FEC4}"], // ط TAH
        "\u{0638}" => ["\u{FEC5}", "\u{FEC6}", "\u{FEC7}", "\u{FEC8}"], // ظ ZAH
        "\u{0639}" => ["\u{FEC9}", "\u{FECA}", "\u{FECB}", "\u{FECC}"], // ع AIN
        "\u{063A}" => ["\u{FECD}", "\u{FECE}", "\u{FECF}", "\u{FED0}"], // غ GHAIN
        "\u{0640}" => ["\u{0640}", "\u{0640}", "\u{0640}", "\u{0640}"], // ـ TATWEEL
        "\u{0641}" => ["\u{FED1}", "\u{FED2}", "\u{FED3}", "\u{FED4}"], // ف FEH
        "\u{0642}" => ["\u{FED5}", "\u{FED6}", "\u{FED7}", "\u{FED8}"], // ق QAF
        "\u{0643}" => ["\u{FED9}", "\u{FEDA}", "\u{FEDB}", "\u{FEDC}"], // ك KAF
        "\u{0644}" => ["\u{FEDD}", "\u{FEDE}", "\u{FEDF}", "\u{FEE0}"], // ل LAM
        "\u{0645}" => ["\u{FEE1}", "\u{FEE2}", "\u{FEE3}", "\u{FEE4}"], // م MEEM
        "\u{0646}" => ["\u{FEE5}", "\u{FEE6}", "\u{FEE7}", "\u{FEE8}"], // ن NOON
        "\u{0647}" => ["\u{FEE9}", "\u{FEEA}", "\u{FEEB}", "\u{FEEC}"], // ه HEH
        "\u{0648}" => ["\u{FEED}", "\u{FEEE}", null, null],         // و WAW
        "\u{0649}" => ["\u{FEEF}", "\u{FEF0}", null, null],         // ى ALEF MAKSURA
        "\u{064A}" => ["\u{FEF1}", "\u{FEF2}", "\u{FEF3}", "\u{FEF4}"], // ي YEH
        "\u{0671}" => ["\u{FB50}", "\u{FB51}", null, null],         // ٱ ALEF WASLA
        "\u{067E}" => ["\u{FB56}", "\u{FB57}", "\u{FB58}", "\u{FB59}"], // پ PEH
        "\u{0686}" => ["\u{FB7A}", "\u{FB7B}", "\u{FB7C}", "\u{FB7D}"], // چ TCHEH
        "\u{0698}" => ["\u{FB8A}", "\u{FB8B}", null, null],         // ژ JEH
        "\u{06A4}" => ["\u{FB6A}", "\u{FB6B}", "\u{FB6C}", "\u{FB6D}"], // ڤ VEH
        "\u{06AF}" => ["\u{FB92}", "\u{FB93}", "\u{FB94}", "\u{FB95}"], // گ GAF
        "\u{06CC}" => ["\u{FBFC}", "\u{FBFD}", "\u{FBFE}", "\u{FBFF}"], // ی FARSI YEH
    ];

    /**
     * Lam-Alif ligatures: [alef_char => [isolated, final]].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const LAM_ALEF_LIGATURES = [
        "\u{0622}" => ["\u{FEF5}", "\u{FEF6}"], // لآ
        "\u{0623}" => ["\u{FEF7}", "\u{FEF8}"], // لأ
        "\u{0625}" => ["\u{FEF9}", "\u{FEFA}"], // لإ
        "\u{0627}" => ["\u{FEFB}", "\u{FEFC}"], // لا
    ];

    public static function shapeForPdf(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        if (! preg_match(self::ARABIC_CHAR_PATTERN, $text)) {
            return $text;
        }

        return (string) preg_replace_callback(
            self::RTL_RUN_PATTERN,
            static fn (array $matches): string => self::shapeRtlRun($matches[0]),
            $text
        );
    }

    private static function shapeRtlRun(string $run): string
    {
        $chars = preg_split('//u', $run, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false || $chars === []) {
            return $run;
        }

        // Group each base character with its trailing combining diacritics (harakat).
        $clusters = [];
        foreach ($chars as $ch) {
            if (self::isCombiningMark($ch) && $clusters !== []) {
                $clusters[count($clusters) - 1]['marks'] .= $ch;
            } else {
                $clusters[] = ['char' => $ch, 'marks' => ''];
            }
        }

        // Merge Lam + Alef into a single ligature cluster.
        $merged = [];
        $total = count($clusters);
        for ($i = 0; $i < $total; $i++) {
            $curr = $clusters[$i];
            if (
                $curr['char'] === "\u{0644}"
                && $i + 1 < $total
                && isset(self::LAM_ALEF_LIGATURES[$clusters[$i + 1]['char']])
            ) {
                $next = $clusters[$i + 1];
                $merged[] = [
                    'char' => $curr['char'].$next['char'],
                    'marks' => $curr['marks'].$next['marks'],
                    'ligature' => $next['char'],
                ];
                $i++;
            } else {
                $merged[] = [
                    'char' => $curr['char'],
                    'marks' => $curr['marks'],
                    'ligature' => null,
                ];
            }
        }

        // Shape each cluster based on its neighbors and reverse into visual LTR order.
        $shaped = [];
        $count = count($merged);
        for ($i = 0; $i < $count; $i++) {
            $item = $merged[$i];
            $prevConnects = $i > 0 && self::canConnectAfter($merged[$i - 1]);
            $nextConnects = $i + 1 < $count && self::canConnectBefore($merged[$i + 1]);

            if ($item['ligature'] !== null) {
                $forms = self::LAM_ALEF_LIGATURES[$item['ligature']];
                $glyph = $prevConnects ? $forms[1] : $forms[0];
                $shaped[] = $glyph.$item['marks'];

                continue;
            }

            $ch = $item['char'];
            if (! isset(self::GLYPHS[$ch])) {
                $shaped[] = $ch.$item['marks'];

                continue;
            }

            $forms = self::GLYPHS[$ch];
            $canJoinLeft = $forms[2] !== null;

            if ($prevConnects && $nextConnects && $canJoinLeft && $forms[3] !== null) {
                $glyph = $forms[3]; // medial
            } elseif ($prevConnects) {
                $glyph = $forms[1]; // final
            } elseif ($nextConnects && $canJoinLeft) {
                $glyph = $forms[2]; // initial
            } else {
                $glyph = $forms[0]; // isolated
            }

            $shaped[] = $glyph.$item['marks'];
        }

        // Preserve LTR digit sequences inside an RTL run (e.g. "١٢" vs "12").
        return implode('', self::reverseWithLtrDigits($shaped));
    }

    /**
     * @param  array{char: string, marks: string, ligature: ?string}  $item
     */
    private static function canConnectAfter(array $item): bool
    {
        if ($item['ligature'] !== null) {
            return false;
        }

        $ch = $item['char'];

        return isset(self::GLYPHS[$ch]) && self::GLYPHS[$ch][2] !== null;
    }

    /**
     * @param  array{char: string, marks: string, ligature: ?string}  $item
     */
    private static function canConnectBefore(array $item): bool
    {
        if ($item['ligature'] !== null) {
            return true;
        }

        return isset(self::GLYPHS[$item['char']]);
    }

    private static function isCombiningMark(string $ch): bool
    {
        $code = mb_ord($ch, 'UTF-8');

        return ($code >= 0x064B && $code <= 0x065F)
            || $code === 0x0670
            || ($code >= 0x06D6 && $code <= 0x06ED);
    }

    /**
     * @param  array<int, string>  $tokens
     * @return array<int, string>
     */
    private static function reverseWithLtrDigits(array $tokens): array
    {
        $reversed = array_reverse($tokens);
        $result = [];
        $digitBuffer = [];

        foreach ($reversed as $token) {
            if (preg_match('/^[0-9]$/', $token)) {
                $digitBuffer[] = $token;
            } else {
                if ($digitBuffer !== []) {
                    array_push($result, ...array_reverse($digitBuffer));
                    $digitBuffer = [];
                }
                $result[] = $token;
            }
        }

        if ($digitBuffer !== []) {
            array_push($result, ...array_reverse($digitBuffer));
        }

        return $result;
    }
}
