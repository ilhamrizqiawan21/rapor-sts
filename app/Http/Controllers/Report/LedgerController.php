<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Period;
use App\Services\Export\LedgerExcelExporter;
use App\Services\Report\PdfRenderer;
use App\Services\Report\ReportCardBuilder;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LedgerController extends Controller
{
    public function downloadPdf(
        Classroom $classroom,
        ReportCardBuilder $builder,
        PdfRenderer $renderer
    ): Response {
        $this->authorize('print', $classroom);

        /** @var Period $period */
        $period = app('activePeriod');

        $ledgerData = $builder->buildLedgerData($classroom, $period);
        $paperSize = $ledgerData['printSetting']->paper_size ?? 'A4';

        $pdf = $renderer->render('pdf.ledger', $ledgerData, $paperSize, 'landscape');

        $cleanClass = preg_replace('/\s+/', '_', trim($classroom->name));
        $filename = "Leger_STS_{$cleanClass}.pdf";

        return $pdf->download($filename);
    }

    public function downloadExcel(
        Classroom $classroom,
        LedgerExcelExporter $exporter
    ): StreamedResponse {
        $this->authorize('print', $classroom);

        /** @var Period $period */
        $period = app('activePeriod');

        $spreadsheet = $exporter->export($classroom, $period);
        $cleanClass = preg_replace('/\s+/', '_', trim($classroom->name));
        $filename = "Leger_STS_{$cleanClass}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
