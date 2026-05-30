<?php

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $murid = Auth::user();
        $mataPelajaran = $request->input('mata_pelajaran');
        
        $query = StudentExam::where('user_id', $murid->id)
            ->where('status', 'selesai')
            ->with('examSet');
        
        if ($mataPelajaran) {
            $query->whereHas('examSet', function($q) {
                $q->where('mata_pelajaran', request('mata_pelajaran'));
            });
        }
        
        // Filter lulus/tidak lulus
        if ($request->filled('lulus')) {
            $lulus = $request->input('lulus') === 'true';
            $query->where('lulus', $lulus);
        }
        
        $results = $query->latest('finished_at')
            ->paginate(10);
        
        $mataPelajaranOptions = StudentExam::where('user_id', $murid->id)
            ->where('status', 'selesai')
            ->with('examSet')
            ->get()
            ->pluck('examSet.mata_pelajaran')
            ->unique();
        
        return view('murid.results.index', [
            'results' => $results,
            'mataPelajaranOptions' => $mataPelajaranOptions,
        ]);
    }

    public function show(StudentExam $studentExam)
    {
        $murid = Auth::user();
        
        if ($studentExam->user_id !== $murid->id) {
            return redirect()->route('murid.results.index')
                ->with('error', 'Akses tidak diizinkan.');
        }
        
        $studentExam->load('examSet.questions.options', 'answers.question.options', 'answers.selectedOption');
        
        // Hitung statistik jawaban
        $totalSoal = $studentExam->examSet->questions()->count();
        $soalDijawab = $studentExam->answers()->count();
        $soalBenar = $studentExam->answers()->where('is_correct', true)->count();
        $soalSalah = $soalDijawab - $soalBenar;
        $soalTidakDijawab = $totalSoal - $soalDijawab;
        
        return view('murid.results.show', [
            'studentExam' => $studentExam,
            'totalSoal' => $totalSoal,
            'soalDijawab' => $soalDijawab,
            'soalBenar' => $soalBenar,
            'soalSalah' => $soalSalah,
            'soalTidakDijawab' => $soalTidakDijawab,
        ]);
    }

    public function leaderboard(Request $request)
    {
        $murid = Auth::user();
        $examSetId = $request->input('exam_set_id');
        
        if (!$examSetId) {
            return redirect()->route('murid.results.index')
                ->with('error', 'Pilih ujian terlebih dahulu.');
        }
        
        $leaderboard = StudentExam::where('exam_set_id', $examSetId)
            ->where('status', 'selesai')
            ->with('user')
            ->orderBy('nilai_akhir', 'desc')
            ->paginate(10);
        
        $currentRank = StudentExam::where('exam_set_id', $examSetId)
            ->where('status', 'selesai')
            ->where('nilai_akhir', '>', 
                StudentExam::where('exam_set_id', $examSetId)
                    ->where('user_id', $murid->id)
                    ->value('nilai_akhir') ?? 0
            )
            ->count() + 1;
        
        return view('murid.results.leaderboard', [
            'leaderboard' => $leaderboard,
            'currentRank' => $currentRank,
        ]);
    }
}
