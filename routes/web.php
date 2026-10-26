<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Homeroom\HomeroomRecordController;
use App\Http\Controllers\Homeroom\LockController as HomeroomLockController;
use App\Http\Controllers\Homeroom\StatusController as HomeroomStatusController;
use App\Http\Controllers\Kepala\DashboardController as KepalaDashboardController;
use App\Http\Controllers\Kepala\LegerController as KepalaLegerController;
use App\Http\Controllers\Kepala\ReportController as KepalaReportController;
use App\Http\Controllers\Kepala\TeacherPerformanceController;
use App\Http\Controllers\Operator\AcademicYearController;
use App\Http\Controllers\Operator\BackupController;
use App\Http\Controllers\Operator\ClassroomController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\KktpDefaultController;
use App\Http\Controllers\Operator\PeriodController;
use App\Http\Controllers\Operator\PrintSettingController;
use App\Http\Controllers\Operator\ProfileController;
use App\Http\Controllers\Operator\StatusController as OperatorStatusController;
use App\Http\Controllers\Operator\StudentController;
use App\Http\Controllers\Operator\SubjectController;
use App\Http\Controllers\Operator\TeacherController;
use App\Http\Controllers\Operator\TeachingAssignmentController;
use App\Http\Controllers\Report\LedgerController;
use App\Http\Controllers\Report\ReportCardController;
use App\Http\Controllers\Teacher\AssessmentController;
use App\Http\Controllers\Teacher\CompetencyOverrideController;
use App\Http\Controllers\Teacher\DashboardController as TeacherDashboardController;
use App\Http\Controllers\Teacher\ImportController;
use App\Http\Controllers\Teacher\LearningScopeController;
use App\Http\Controllers\Teacher\RdmExportController;
use App\Http\Controllers\Teacher\StsScoreController;
use App\Http\Controllers\Teacher\SubmissionController;
use App\Http\Controllers\Teacher\WeightController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// Akun & Profil (semua role: foto & ganti password sendiri)
Route::middleware('auth')->prefix('akun')->name('akun.')->group(function () {
    Route::get('/', [AccountController::class, 'edit'])->name('edit');
    Route::post('/foto', [AccountController::class, 'updatePhoto'])->name('foto');
    Route::delete('/foto', [AccountController::class, 'removePhoto'])->name('foto.hapus');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('password');
});

// Root redirect
Route::get('/', [LoginController::class, 'home'])->name('home');

// Operator Routes
Route::middleware(['auth', 'role:operator'])->prefix('operator')->name('operator.')->group(function () {
    Route::get('/dashboard', [OperatorDashboardController::class, 'index'])->name('dashboard');

    // Profil Madrasah & Branding
    Route::get('/profil', [ProfileController::class, 'edit'])->name('profil.edit');
    Route::post('/profil', [ProfileController::class, 'update'])->name('profil.update');

    // Pengaturan Lembaga & Tahun Pelajaran / Periode
    Route::get('/lembaga', [AcademicYearController::class, 'index'])->name('lembaga.index');
    Route::post('/lembaga/tahun', [AcademicYearController::class, 'store'])->name('lembaga.tahun.store');
    Route::delete('/lembaga/tahun/{academicYear}', [AcademicYearController::class, 'destroy'])->name('lembaga.tahun.destroy');
    Route::post('/lembaga/periode/{period}/aktif', [PeriodController::class, 'activate'])->name('lembaga.periode.activate');

    // Mata Pelajaran
    Route::get('/mapel', [SubjectController::class, 'index'])->name('subjects.index');
    Route::post('/mapel', [SubjectController::class, 'store'])->name('subjects.store');
    Route::put('/mapel/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/mapel/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');

    // Kelas
    Route::get('/kelas', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::post('/kelas', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::post('/kelas/batch', [ClassroomController::class, 'batchStore'])->name('classrooms.batch');
    Route::put('/kelas/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::patch('/kelas/{classroom}/wali-kelas', [ClassroomController::class, 'updateHomeroom'])->name('classrooms.homeroom.update');
    Route::delete('/kelas/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');

    // Guru
    Route::get('/guru', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('/guru', [TeacherController::class, 'store'])->name('teachers.store');
    Route::delete('/guru/bulk-destroy', [TeacherController::class, 'bulkDestroy'])->name('teachers.bulk-destroy');
    Route::put('/guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::delete('/guru/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
    Route::post('/guru/{teacher}/toggle', [TeacherController::class, 'toggleStatus'])->name('teachers.toggle');
    Route::get('/guru/template', [TeacherController::class, 'template'])->name('teachers.template');
    Route::post('/guru/import', [TeacherController::class, 'import'])->name('teachers.import');

    // Siswa & Enrollment
    Route::get('/siswa', [StudentController::class, 'index'])->name('students.index');
    Route::post('/siswa', [StudentController::class, 'store'])->name('students.store');
    Route::delete('/siswa/bulk-destroy', [StudentController::class, 'bulkDestroy'])->name('students.bulk-destroy');
    Route::put('/siswa/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::post('/siswa/naik-kelas', [StudentController::class, 'promoteClass'])->name('students.promote');
    Route::post('/siswa/{student}/toggle', [StudentController::class, 'toggleStatus'])->name('students.toggle');
    Route::get('/siswa/template', [StudentController::class, 'template'])->name('students.template');
    Route::post('/siswa/import', [StudentController::class, 'import'])->name('students.import');
    Route::delete('/siswa/{student}', [StudentController::class, 'destroy'])->name('students.destroy');

    // Pembagian Mengajar
    Route::get('/mengajar', [TeachingAssignmentController::class, 'index'])->name('mengajar.index');
    Route::post('/mengajar', [TeachingAssignmentController::class, 'storeOrUpdate'])->name('mengajar.store');
    Route::delete('/mengajar/{teachingAssignment}', [TeachingAssignmentController::class, 'destroy'])->name('mengajar.destroy');
    Route::post('/mengajar/salin', [TeachingAssignmentController::class, 'copy'])->name('mengajar.copy');

    // KKTP Default
    Route::get('/kktp', [KktpDefaultController::class, 'index'])->name('kktp.index');
    Route::post('/kktp', [KktpDefaultController::class, 'storeOrUpdate'])->name('kktp.store');

    // Pengaturan Cetak Rapor STS (Mockup 05)
    Route::get('/pengaturan-cetak', [PrintSettingController::class, 'edit'])->name('print-settings.edit');
    Route::post('/pengaturan-cetak', [PrintSettingController::class, 'update'])->name('print-settings.update');

    // Status Nilai Operator (Mockup 06)
    Route::get('/status-nilai', [OperatorStatusController::class, 'index'])->name('status.index');
    Route::post('/status-nilai/kunci/{classroom}', [HomeroomLockController::class, 'lock'])->name('status.lock');
    Route::post('/status-nilai/buka/{classroom}', [HomeroomLockController::class, 'unlock'])->name('status.unlock');
    Route::post('/status-nilai/batal-kirim/{teachingAssignment}', [HomeroomStatusController::class, 'unsubmit'])->name('status.unsubmit');

    // Backup dan restore database
    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::post('/backup', [BackupController::class, 'store'])->name('backup.store');
    Route::post('/backup/restore', [BackupController::class, 'restore'])->name('backup.restore');
    Route::get('/backup/{file}', [BackupController::class, 'download'])->name('backup.download');

    // Cetak Rapor Operator (Mockup 07)
    Route::get('/cetak-rapor', [ReportCardController::class, 'operatorIndex'])->name('rapor.index');
});

// Guru Routes
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', [TeacherDashboardController::class, 'index'])->name('dashboard');

    // Penilaian Ringkasan & Kirim Nilai (Mockup 09)
    Route::get('/mengajar/{teachingAssignment}', [AssessmentController::class, 'index'])->name('penilaian.index');
    Route::post('/mengajar/{teachingAssignment}/kirim', [SubmissionController::class, 'submit'])->name('penilaian.submit');

    // Bobot Nilai (Mockup 08)
    Route::get('/mengajar/{teachingAssignment}/bobot', [WeightController::class, 'edit'])->name('bobot.edit');
    Route::put('/mengajar/{teachingAssignment}/bobot', [WeightController::class, 'update'])->name('bobot.update');

    // Nilai Sumatif LM (Mockup 10 & 11)
    Route::get('/mengajar/{teachingAssignment}/sumatif', [LearningScopeController::class, 'index'])->name('sumatif.index');
    Route::post('/mengajar/{teachingAssignment}/sumatif', [LearningScopeController::class, 'store'])->name('sumatif.store');
    Route::put('/mengajar/{teachingAssignment}/sumatif/{learningScope}', [LearningScopeController::class, 'update'])->name('sumatif.update');
    Route::delete('/mengajar/{teachingAssignment}/sumatif/{learningScope}', [LearningScopeController::class, 'destroy'])->name('sumatif.destroy');
    Route::get('/mengajar/{teachingAssignment}/sumatif/template', [ImportController::class, 'sumatifTemplate'])->name('sumatif.template');
    Route::post('/mengajar/{teachingAssignment}/sumatif/import', [ImportController::class, 'sumatifImport'])->name('sumatif.import');

    // Nilai Tes STS (Mockup 12)
    Route::get('/mengajar/{teachingAssignment}/sts', [StsScoreController::class, 'index'])->name('sts.index');
    Route::put('/mengajar/{teachingAssignment}/sts', [StsScoreController::class, 'update'])->name('sts.update');
    Route::get('/mengajar/{teachingAssignment}/sts/template', [ImportController::class, 'stsTemplate'])->name('sts.template');
    Route::post('/mengajar/{teachingAssignment}/sts/import', [ImportController::class, 'stsImport'])->name('sts.import');

    // Override Capaian Kompetensi (FR-NIL-09)
    Route::put('/mengajar/{teachingAssignment}/capaian/{student}', [CompetencyOverrideController::class, 'update'])->name('capaian.update');
    Route::delete('/mengajar/{teachingAssignment}/capaian/{student}', [CompetencyOverrideController::class, 'destroy'])->name('capaian.destroy');

    // Export Nilai ke Format Template RDM (ADR-016 & docs/08 §4)
    Route::get('/mengajar/{teachingAssignment}/rdm/sumatif', [RdmExportController::class, 'exportSummative'])->name('rdm.sumatif.export');
    Route::post('/mengajar/{teachingAssignment}/rdm/sumatif/fill', [RdmExportController::class, 'fillSummativeTemplate'])->name('rdm.sumatif.fill');
    Route::get('/mengajar/{teachingAssignment}/rdm/sts', [RdmExportController::class, 'exportSts'])->name('rdm.sts.export');
    Route::post('/mengajar/{teachingAssignment}/rdm/sts/fill', [RdmExportController::class, 'fillStsTemplate'])->name('rdm.sts.fill');
});

// Wali Kelas Routes (FR-WAL-*)
Route::middleware(['auth', 'role:guru'])->prefix('wali')->name('wali.')->group(function () {
    // Kehadiran & Catatan (Mockup 13)
    Route::get('/kehadiran', [HomeroomRecordController::class, 'index'])->name('kehadiran.index');
    Route::post('/kehadiran', [HomeroomRecordController::class, 'update'])->name('kehadiran.update');
    Route::post('/kehadiran/massal', [HomeroomRecordController::class, 'batchNote'])->name('kehadiran.batch_note');
    Route::get('/kehadiran/template', [HomeroomRecordController::class, 'template'])->name('kehadiran.template');
    Route::post('/kehadiran/import', [HomeroomRecordController::class, 'import'])->name('kehadiran.import');

    // Status Nilai & Kunci (Mockup 06)
    Route::get('/status', [HomeroomStatusController::class, 'index'])->name('status.index');
    Route::post('/status/kunci/{classroom}', [HomeroomLockController::class, 'lock'])->name('status.lock');
    Route::post('/status/buka/{classroom}', [HomeroomLockController::class, 'unlock'])->name('status.unlock');
    Route::post('/status/batal-kirim/{teachingAssignment}', [HomeroomStatusController::class, 'unsubmit'])->name('status.unsubmit');

    // Cetak Rapor STS (Mockup 07)
    Route::get('/rapor', [ReportCardController::class, 'homeroomIndex'])->name('rapor.index');
});

// Rapor Cetak PDF & Leger (Shared Download Routes)
Route::middleware('auth')->prefix('rapor')->name('rapor.')->group(function () {
    Route::get('/{classroom}/siswa/{student}/pdf', [ReportCardController::class, 'downloadSinglePdf'])->name('student.pdf');
    Route::get('/{classroom}/semua/pdf', [ReportCardController::class, 'downloadAllPdf'])->name('classroom.pdf');
    Route::get('/{classroom}/leger/pdf', [LedgerController::class, 'downloadPdf'])->name('leger.pdf');
    Route::get('/{classroom}/leger/excel', [LedgerController::class, 'downloadExcel'])->name('leger.excel');
});

// Kepala Routes (Read-Only)
Route::middleware(['auth', 'role:kepala', 'headmaster.readonly'])->prefix('kepala')->name('kepala.')->group(function () {
    Route::get('/dashboard', [KepalaDashboardController::class, 'index'])->name('dashboard');
    Route::get('/leger', [KepalaLegerController::class, 'index'])->name('leger.index');
    Route::get('/kinerja-guru', [TeacherPerformanceController::class, 'index'])->name('kinerja.index');
    Route::get('/rapor', [KepalaReportController::class, 'index'])->name('rapor.index');
    Route::get('/unduh/excel', [KepalaReportController::class, 'downloadExcel'])->name('unduh.excel');
    Route::get('/unduh/pdf', [KepalaReportController::class, 'downloadPdf'])->name('unduh.pdf');
});
