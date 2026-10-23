<?php

namespace App\Services\Report;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;

class PdfRenderer
{
    /**
     * Render view Blade ke instance DomPDF dengan ukuran kertas dan orientasi yang tepat.
     *
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data, string $paperSize = 'A4', string $orientation = 'portrait'): DomPdfWrapper
    {
        // PDF satu kelas dibuat sinkron (ADR-009): longgarkan batas bila host mengizinkan.
        @set_time_limit(300);
        if (self::memoryBytes() < 512 * 1024 * 1024 && self::memoryBytes() > 0) {
            @ini_set('memory_limit', '512M');
        }

        $pdf = Pdf::loadView($view, $data);

        // Pengaturan kertas: A4 (210x297 mm) atau F4 / Folio (215x330 mm = 609.45 x 935.43 pt)
        if (strtoupper($paperSize) === 'F4' || strtoupper($paperSize) === 'FOLIO') {
            $pdf->setPaper([0, 0, 609.45, 935.43], $orientation);
        } else {
            $pdf->setPaper('a4', $orientation);
        }

        $pdf->setOption('isRemoteEnabled', false); // logo memakai data URL; cegah SSRF
        $pdf->setOption('isHtml5ParserEnabled', true);

        return $pdf;
    }

    private static function memoryBytes(): int
    {
        $v = trim((string) ini_get('memory_limit'));
        if ($v === '-1' || $v === '') {
            return 0;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) {
            'g' => $n * 1024 ** 3,
            'm' => $n * 1024 ** 2,
            'k' => $n * 1024,
            default => $n,
        };
    }
}
