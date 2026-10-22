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
        $pdf = Pdf::loadView($view, $data);

        // Pengaturan kertas: A4 (210x297 mm) atau F4 / Folio (215x330 mm = 609.45 x 935.43 pt)
        if (strtoupper($paperSize) === 'F4' || strtoupper($paperSize) === 'FOLIO') {
            $pdf->setPaper([0, 0, 609.45, 935.43], $orientation);
        } else {
            $pdf->setPaper('a4', $orientation);
        }

        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);

        return $pdf;
    }
}
