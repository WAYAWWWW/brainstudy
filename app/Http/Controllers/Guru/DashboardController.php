<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\User;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $guru = Auth::user();
        
        // Total soal aktif
        $totalSoalAktif = ExamSet::where('guru_id', $guru->id)
            ->where('status', 'aktif')
            ->withCount('questions')
            ->get()
            ->sum('questions_count');
        
        // Total murid terdaftar
        $totalMurid = User::where('guru_id', $guru->id)->count();
        
        // Total pengerjaan hari ini
        $totalPengerjaanHariIni = StudentExam::whereHas('examSet', function($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })
        ->whereDate('finished_at', Carbon::today())
        ->count();
        
        // Rata-rata nilai
        $rataRataNilai = StudentExam::whereHas('examSet', function($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })
        ->whereNotNull('nilai_akhir')
        ->avg('nilai_akhir');
        
        // 5 ujian terbaru
        $ujianTerbaru = ExamSet::where('guru_id', $guru->id)
            ->latest()
            ->take(5)
            ->with('questions')
            ->withCount('studentExams')
            ->get();
        
        // Data untuk grafik batang perbandingan nilai
        $grafikNilai = ExamSet::where('guru_id', $guru->id)
            ->with('studentExams')
            ->get()
            ->map(function($exam) {
                return [
                    'judul' => $exam->judul,
                    'rata_rata' => $exam->studentExams->whereNotNull('nilai_akhir')->avg('nilai_akhir') ?? 0,
                ];
            })
            ->take(5);
        
        // Data untuk grafik donut lulus vs tidak lulus
        $totalLulus = StudentExam::whereHas('examSet', function($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })->where('lulus', true)->count();
        
        $totalTidakLulus = StudentExam::whereHas('examSet', function($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })->where('lulus', false)->count();
        
        // 5 aktivitas terkini murid
        $aktivitasTerkini = StudentExam::whereHas('examSet', function($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })
        ->with('user', 'examSet')
        ->whereNotNull('finished_at')
        ->latest('finished_at')
        ->take(5)
        ->get();
        
        return view('guru.dashboard', [
            'totalSoalAktif' => $totalSoalAktif,
            'totalMurid' => $totalMurid,
            'totalPengerjaanHariIni' => $totalPengerjaanHariIni,
            'rataRataNilai' => round($rataRataNilai ?? 0, 2),
            'ujianTerbaru' => $ujianTerbaru,
            'grafikNilai' => $grafikNilai,
            'totalLulus' => $totalLulus,
            'totalTidakLulus' => $totalTidakLulus,
            'aktivitasTerkini' => $aktivitasTerkini,
        ]);
    }
}
