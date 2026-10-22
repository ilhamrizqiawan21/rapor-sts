<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\RdmTemplateFillRequest;
use App\Models\TeachingAssignment;
use App\Services\Export\RdmExporter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RdmExportController extends Controller
{
    /**
     * Unduh berkas format RDM untuk nilai Sumatif LM (menggunakan basis template bawaan).
     */
    public function exportSummative(
        TeachingAssignment $teachingAssignment,
        RdmExporter $exporter
    ): StreamedResponse {
        $this->authorize('view', $teachingAssignment);

        $spreadsheet = $exporter->exportSummative($teachingAssignment);

        $classroomSlug = str_replace('.', '_', $teachingAssignment->classroom->name);
        $subjectCode = $teachingAssignment->subject->code;
        $filename = "RDM_Sumatif_{$subjectCode}_{$classroomSlug}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Mengisi berkas template RDM Sumatif asli yang diunggah oleh guru dengan nilai dari aplikasi.
     */
    public function fillSummativeTemplate(
        RdmTemplateFillRequest $request,
        TeachingAssignment $teachingAssignment,
        RdmExporter $exporter
    ): StreamedResponse {
        $this->authorize('view', $teachingAssignment);

        $uploadedFile = $request->file('template_file');
        $spreadsheet = $exporter->exportSummative(
            assignment: $teachingAssignment,
            templatePath: $uploadedFile->getRealPath()
        );

        $classroomSlug = str_replace('.', '_', $teachingAssignment->classroom->name);
        $subjectCode = $teachingAssignment->subject->code;
        $filename = "RDM_Sumatif_Terisi_{$subjectCode}_{$classroomSlug}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Unduh berkas format RDM untuk nilai STS / Tes (menggunakan basis template bawaan).
     */
    public function exportSts(
        TeachingAssignment $teachingAssignment,
        RdmExporter $exporter
    ): StreamedResponse {
        $this->authorize('view', $teachingAssignment);

        $spreadsheet = $exporter->exportSts($teachingAssignment);

        $classroomSlug = str_replace('.', '_', $teachingAssignment->classroom->name);
        $subjectCode = $teachingAssignment->subject->code;
        $filename = "RDM_STS_{$subjectCode}_{$classroomSlug}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Mengisi berkas template RDM Tes/STS/PAT asli yang diunggah oleh guru dengan nilai dari aplikasi.
     */
    public function fillStsTemplate(
        RdmTemplateFillRequest $request,
        TeachingAssignment $teachingAssignment,
        RdmExporter $exporter
    ): StreamedResponse {
        $this->authorize('view', $teachingAssignment);

        $uploadedFile = $request->file('template_file');
        $spreadsheet = $exporter->exportSts(
            assignment: $teachingAssignment,
            templatePath: $uploadedFile->getRealPath()
        );

        $classroomSlug = str_replace('.', '_', $teachingAssignment->classroom->name);
        $subjectCode = $teachingAssignment->subject->code;
        $filename = "RDM_STS_Terisi_{$subjectCode}_{$classroomSlug}.xlsx";

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
