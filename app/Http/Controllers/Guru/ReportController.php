<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\StudentExam;
use App\Models\StudentAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function statistics(ExamSet $examSet)
    {
        $this->authorize('view', $examSet);
        
        $examSet->load('studentExams.user', 'questions');
        
        $studentExams = $examSet->studentExams()
            ->with('user')
            ->where('status', 'selesai')
            ->orderBy('nilai_akhir', 'desc')
            ->get();
        
        // Histogram data
        $scoreDistribution = [];
        for ($i = 0; $i <= 100; $i += 10) {
            $scoreDistribution[$i . '-' . ($i + 9)] = $studentExams
                ->whereBetween('nilai_akhir', [$i, $i + 9])
                ->count();
        }
        
        return view('guru.reports.statistics', [
            'examSet' => $examSet,
            'studentExams' => $studentExams,
            'scoreDistribution' => $scoreDistribution,
        ]);
    }

    public function history(Request $request)
    {
        $guru = Auth::user();
        
        $query = StudentExam::whereHas('examSet', function($q) use ($guru) {
            $q->where('guru_id', $guru->id);
        })->with('user', 'examSet');
        
        // Filter
        if ($request->filled('tanggal')) {
            $query->whereDate('created_at', $request->input('tanggal'));
        }
        
        if ($request->filled('kelas')) {
            $query->whereHas('user', function($q) {
                $q->where('kelas', request('kelas'));
            });
        }
        
        if ($request->filled('exam_set_id')) {
            $query->where('exam_set_id', $request->input('exam_set_id'));
        }
        
        if ($request->filled('lulus')) {
            $lulus = $request->input('lulus') === 'true';
            $query->where('lulus', $lulus);
        }
        
        $studentExams = $query->latest()->paginate(15);
        
        $examSets = ExamSet::where('guru_id', $guru->id)->pluck('judul', 'id');
        $kelasOptions = User::where('guru_id', $guru->id)
            ->where('role', 'murid')
            ->distinct('kelas')
            ->pluck('kelas');
        
        return view('guru.reports.history', [
            'studentExams' => $studentExams,
            'examSets' => $examSets,
            'kelasOptions' => $kelasOptions,
        ]);
    }

    public function detail(StudentExam $studentExam)
    {
        $this->authorize('view', $studentExam->examSet);
        
        $studentExam->load('user', 'examSet.questions.options', 'answers.selectedOption', 'answers.question');
        
        return view('guru.reports.detail', [
            'studentExam' => $studentExam,
        ]);
    }

    public function exportExcel(ExamSet $examSet)
    {
        $this->authorize('view', $examSet);
        
        return Excel::download(
            new \App\Exports\StudentResultsExport($examSet),
            'hasil_ujian_' . $examSet->judul . '.xlsx'
        );
    }

    public function exportPdf(StudentExam $studentExam)
    {
        $this->authorize('view', $studentExam->examSet);
        
        $studentExam->load('user', 'examSet', 'answers.question.options', 'answers.selectedOption');
        
        $pdf = Pdf::loadView('guru.reports.pdf', [
            'studentExam' => $studentExam,
        ]);
        
        return $pdf->download('hasil_ujian_' . $studentExam->user->name . '.pdf');
    }
}
