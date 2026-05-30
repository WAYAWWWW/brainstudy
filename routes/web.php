<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Guru\DashboardController as GuruDashboardController;
use App\Http\Controllers\Guru\ExamSetController;
use App\Http\Controllers\Guru\QuestionController;
use App\Http\Controllers\Guru\StudentController;
use App\Http\Controllers\Guru\ReportController;
use App\Http\Controllers\Murid\DashboardController as MuridDashboardController;
use App\Http\Controllers\Murid\ExamController;
use App\Http\Controllers\Murid\ResultController;

Route::get('/', function () {
    return view('welcome');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Guru Routes
Route::middleware(['auth', 'role.guru'])->prefix('guru')->name('guru.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [GuruDashboardController::class, 'index'])->name('dashboard');
    
    // Exam Sets Management
    Route::resource('exam-sets', ExamSetController::class);
    Route::post('exam-sets/{examSet}/regenerate-token', [ExamSetController::class, 'regenerateToken'])->name('exam-sets.regenerate-token');
    Route::post('exam-sets/{examSet}/duplicate', [ExamSetController::class, 'duplicate'])->name('exam-sets.duplicate');
    
    // Questions Management
    Route::get('exam-sets/{examSet}/questions/create', [QuestionController::class, 'create'])->name('exam-sets.questions.create');
    Route::post('exam-sets/{examSet}/questions', [QuestionController::class, 'store'])->name('exam-sets.questions.store');
    Route::patch('exam-sets/{examSet}/questions/{question}', [QuestionController::class, 'update'])->name('exam-sets.questions.update');
    Route::delete('questions/{question}', [QuestionController::class, 'destroy'])->name('questions.destroy');
    Route::post('exam-sets/{examSet}/questions/reorder', [QuestionController::class, 'reorder'])->name('exam-sets.questions.reorder');
    
    // Student Management
    Route::resource('students', StudentController::class)->except('show');
    Route::post('students/{student}/deactivate', [StudentController::class, 'deactivate'])->name('students.deactivate');
    Route::get('students/import/template', [StudentController::class, 'importTemplate'])->name('students.import.template');
    Route::get('students/import/form', [StudentController::class, 'importForm'])->name('students.import.form');
    Route::post('students/import/process', [StudentController::class, 'importProcess'])->name('students.import.process');
    
    // Reports
    Route::get('exam-sets/{examSet}/statistics', [ReportController::class, 'statistics'])->name('exam-sets.statistics');
    Route::get('reports/history', [ReportController::class, 'history'])->name('reports.history');
    Route::get('reports/{studentExam}/detail', [ReportController::class, 'detail'])->name('reports.detail');
    Route::get('exam-sets/{examSet}/export/excel', [ReportController::class, 'exportExcel'])->name('exam-sets.export.excel');
    Route::get('reports/{studentExam}/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.export.pdf');
});

// Murid (Student) Routes
Route::middleware(['auth', 'role.murid'])->prefix('murid')->name('murid.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [MuridDashboardController::class, 'index'])->name('dashboard');
    
    // Exams
    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/exams/{examSet}', [ExamController::class, 'show'])->name('exams.show');
    Route::post('/exams/{examSet}/start', [ExamController::class, 'start'])->name('exams.start');
    Route::get('/exams/{studentExam}/work', [ExamController::class, 'work'])->name('exams.work');
    Route::post('/exams/{studentExam}/submit-answer', [ExamController::class, 'submitAnswer'])->name('exams.submit-answer');
    Route::post('/exams/{studentExam}/finish', [ExamController::class, 'finishExam'])->name('exams.finish');
    Route::get('/exams/{studentExam}/result', [ExamController::class, 'result'])->name('exams.result');
    
    // Results
    Route::get('/results', [ResultController::class, 'index'])->name('results.index');
    Route::get('/results/{studentExam}', [ResultController::class, 'show'])->name('results.show');
    Route::get('/results/{studentExam}/leaderboard', [ResultController::class, 'leaderboard'])->name('results.leaderboard');
});

// Fallback route
Route::fallback(function () {
    return view('errors.404');
});
