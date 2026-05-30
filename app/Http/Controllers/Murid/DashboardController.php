<?php

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $murid = Auth::user();
        
        // Total ujian yang tersedia
        $totalUjianTersedia = ExamSet::where('status', 'aktif')
            ->where('kelas_target', $murid->kelas)
            ->count();
        
        // Ujian yang sudah dikerjakan
        $ujianDikerjakan = StudentExam::where('user_id', $murid->id)
            ->where('status', 'selesai')
            ->count();
        
        // Nilai rata-rata
        $nilaiRataRata = StudentExam::where('user_id', $murid->id)
            ->where('status', 'selesai')
            ->whereNotNull('nilai_akhir')
            ->avg('nilai_akhir');
        
        // Total soal yang sudah dijawab
        $totalSoalJawab = StudentExam::where('user_id', $murid->id)
            ->where('status', 'selesai')
            ->withCount('answers')
            ->get()
            ->sum('answers_count');
        
        // Ujian yang sedang dikerjakan (belum selesai)
        $ujianSedangDikerjakan = StudentExam::where('user_id', $murid->id)
            ->where('status', 'sedang_dikerjakan')
            ->with('examSet')
            ->get();
        
        // 5 ujian terbaru yang tersedia
        $ujianTerbaru = ExamSet::where('status', 'aktif')
            ->where('kelas_target', $murid->kelas)
            ->latest()
            ->take(5)
            ->get();
        
        // Ujian dengan nilai tertinggi
        $nilaiTertinggi = StudentExam::where('user_id', $murid->id)
            ->with('examSet')
            ->whereNotNull('nilai_akhir')
            ->orderBy('nilai_akhir', 'desc')
            ->first();
        
        // Riwayat pengerjaan ujian terbaru
        $riwayatTerbaru = StudentExam::where('user_id', $murid->id)
            ->where('status', 'selesai')
            ->with('examSet')
            ->latest('finished_at')
            ->take(5)
            ->get();
        
        return view('murid.dashboard', [
            'totalUjianTersedia' => $totalUjianTersedia,
            'ujianDikerjakan' => $ujianDikerjakan,
            'nilaiRataRata' => round($nilaiRataRata ?? 0, 2),
            'totalSoalJawab' => $totalSoalJawab,
            'ujianSedangDikerjakan' => $ujianSedangDikerjakan,
            'ujianTerbaru' => $ujianTerbaru,
            'nilaiTertinggi' => $nilaiTertinggi,
            'riwayatTerbaru' => $riwayatTerbaru,
        ]);
    }
}
