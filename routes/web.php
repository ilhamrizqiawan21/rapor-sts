<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Operator\AcademicYearController;
use App\Http\Controllers\Operator\ClassroomController;
use App\Http\Controllers\Operator\DashboardController as OperatorDashboardController;
use App\Http\Controllers\Operator\EnrollmentController;
use App\Http\Controllers\Operator\KktpDefaultController;
use App\Http\Controllers\Operator\PeriodController;
use App\Http\Controllers\Operator\ProfileController;
use App\Http\Controllers\Operator\StudentController;
use App\Http\Controllers\Operator\SubjectController;
use App\Http\Controllers\Operator\TeacherController;
use App\Http\Controllers\Operator\TeachingAssignmentController;
use Illuminate\Support\Facades\Route;

// Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

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
    Route::delete('/kelas/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');

    // Guru
    Route::get('/guru', [TeacherController::class, 'index'])->name('teachers.index');
    Route::post('/guru', [TeacherController::class, 'store'])->name('teachers.store');
    Route::put('/guru/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
    Route::post('/guru/{teacher}/toggle', [TeacherController::class, 'toggleStatus'])->name('teachers.toggle');
    Route::get('/guru/template', [TeacherController::class, 'template'])->name('teachers.template');
    Route::post('/guru/import', [TeacherController::class, 'import'])->name('teachers.import');

    // Siswa & Enrollment
    Route::get('/siswa', [StudentController::class, 'index'])->name('students.index');
    Route::post('/siswa', [StudentController::class, 'store'])->name('students.store');
    Route::put('/siswa/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/siswa/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/siswa/{student}/toggle', [StudentController::class, 'toggleStatus'])->name('students.toggle');
    Route::get('/siswa/template', [StudentController::class, 'template'])->name('students.template');
    Route::post('/siswa/import', [StudentController::class, 'import'])->name('students.import');
    Route::post('/siswa/{student}/pindah', [EnrollmentController::class, 'move'])->name('students.move');

    // Pembagian Mengajar
    Route::get('/mengajar', [TeachingAssignmentController::class, 'index'])->name('mengajar.index');
    Route::post('/mengajar', [TeachingAssignmentController::class, 'storeOrUpdate'])->name('mengajar.store');
    Route::delete('/mengajar/{teachingAssignment}', [TeachingAssignmentController::class, 'destroy'])->name('mengajar.destroy');
    Route::post('/mengajar/salin', [TeachingAssignmentController::class, 'copy'])->name('mengajar.copy');

    // KKTP Default
    Route::get('/kktp', [KktpDefaultController::class, 'index'])->name('kktp.index');
    Route::post('/kktp', [KktpDefaultController::class, 'storeOrUpdate'])->name('kktp.store');
});

// Guru Routes
Route::middleware(['auth', 'role:guru'])->prefix('guru')->name('guru.')->group(function () {
    Route::get('/dashboard', function () {
        return view('guru.dashboard');
    })->name('dashboard');
});

// Kepala Routes (Read-Only)
Route::middleware(['auth', 'role:kepala', 'headmaster.readonly'])->prefix('kepala')->name('kepala.')->group(function () {
    Route::get('/dashboard', function () {
        return view('kepala.dashboard');
    })->name('dashboard');
});
