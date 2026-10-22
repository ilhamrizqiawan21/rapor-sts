<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\ScoreImportRequest;
use App\Models\TeachingAssignment;
use App\Services\Import\StsScoreImporter;
use App\Services\Import\SummativeScoreImporter;
use App\Services\Import\TemplateBuilder;
use Illuminate\Http\RedirectResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function sumatifTemplate(TeachingAssignment $teachingAssignment, TemplateBuilder $builder): StreamedResponse
    {
        $this->authorize('view', $teachingAssignment);

        $spreadsheet = $builder->buildSummativeTemplate($teachingAssignment);

        $filename = 'Template_Sumatif_'.$teachingAssignment->subject->code.'_'.str_replace('.', '_', $teachingAssignment->classroom->name).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function sumatifImport(ScoreImportRequest $request, TeachingAssignment $teachingAssignment, SummativeScoreImporter $importer): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        $result = $importer->import($request->file('file'), $teachingAssignment);

        if (! $result['success']) {
            return redirect()->back()->withErrors(['file' => implode('<br>', $result['errors'])]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} nilai sumatif.";
        if (! empty($result['errors'])) {
            $msg .= ' Peringatan: '.implode('; ', $result['errors']);
        }

        return redirect()->route('guru.sumatif.index', $teachingAssignment)->with('success', $msg);
    }

    public function stsTemplate(TeachingAssignment $teachingAssignment, TemplateBuilder $builder): StreamedResponse
    {
        $this->authorize('view', $teachingAssignment);

        $spreadsheet = $builder->buildStsTemplate($teachingAssignment);

        $filename = 'Template_STS_'.$teachingAssignment->subject->code.'_'.str_replace('.', '_', $teachingAssignment->classroom->name).'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function stsImport(ScoreImportRequest $request, TeachingAssignment $teachingAssignment, StsScoreImporter $importer): RedirectResponse
    {
        $this->authorize('update', $teachingAssignment);

        $result = $importer->import($request->file('file'), $teachingAssignment);

        if (! $result['success']) {
            return redirect()->back()->withErrors(['file' => implode('<br>', $result['errors'])]);
        }

        $msg = "Berhasil mengimpor {$result['imported']} data nilai STS.";
        if (! empty($result['errors'])) {
            $msg .= ' Peringatan: '.implode('; ', $result['errors']);
        }

        return redirect()->route('guru.sts.index', $teachingAssignment)->with('success', $msg);
    }
}

