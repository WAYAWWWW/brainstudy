<?php

namespace App\Http\Controllers\Murid;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\StudentExam;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExamController extends Controller
{
    public function index(Request $request)
    {
        $murid = Auth::user();
        $mataPelajaran = $request->input('mata_pelajaran');
        
        $query = ExamSet::where('status', 'aktif')
            ->where('kelas_target', $murid->kelas);
        
        if ($mataPelajaran) {
            $query->where('mata_pelajaran', $mataPelajaran);
        }
        
        $examSets = $query->with('questions')
            ->latest()
            ->paginate(10);
        
        // Ambil status pengerjaan untuk setiap ujian
        foreach ($examSets as $exam) {
            $studentExam = StudentExam::where('user_id', $murid->id)
                ->where('exam_set_id', $exam->id)
                ->first();
            
            $exam->status_pengerjaan = $studentExam->status ?? 'belum_mulai';
            $exam->nilai_akhir = $studentExam->nilai_akhir ?? null;
        }
        
        $mataPelajaranOptions = ExamSet::where('status', 'aktif')
            ->where('kelas_target', $murid->kelas)
            ->distinct('mata_pelajaran')
            ->pluck('mata_pelajaran');
        
        return view('murid.exams.index', [
            'examSets' => $examSets,
            'mataPelajaranOptions' => $mataPelajaranOptions,
        ]);
    }

    public function show(ExamSet $examSet)
    {
        $murid = Auth::user();
        
        // Cek apakah ujian dapat diakses
        if ($examSet->status !== 'aktif' || $examSet->kelas_target !== $murid->kelas) {
            return redirect()->route('murid.exams.index')
                ->with('error', 'Ujian tidak dapat diakses.');
        }
        
        $examSet->load('questions.options');
        $studentExam = StudentExam::where('user_id', $murid->id)
            ->where('exam_set_id', $examSet->id)
            ->first();
        
        return view('murid.exams.show', [
            'examSet' => $examSet,
            'studentExam' => $studentExam,
        ]);
    }

    public function start(ExamSet $examSet)
    {
        $murid = Auth::user();
        
        // Validasi akses ujian
        if ($examSet->status !== 'aktif' || $examSet->kelas_target !== $murid->kelas) {
            return redirect()->route('murid.exams.index')
                ->with('error', 'Ujian tidak dapat diakses.');
        }
        
        // Cek apakah ujian sudah pernah dikerjakan
        $studentExam = StudentExam::where('user_id', $murid->id)
            ->where('exam_set_id', $examSet->id)
            ->first();
        
        if ($studentExam) {
            if ($studentExam->status === 'selesai' && $examSet->max_attempt === 1) {
                return redirect()->route('murid.exams.result', $studentExam->id)
                    ->with('error', 'Anda telah mengerjakan ujian ini.');
            }
            
            if ($studentExam->status === 'sedang_dikerjakan') {
                return redirect()->route('murid.exams.work', $studentExam->id);
            }
        }
        
        // Buat data student exam baru
        $studentExam = StudentExam::create([
            'user_id' => $murid->id,
            'exam_set_id' => $examSet->id,
            'status' => 'sedang_dikerjakan',
            'started_at' => now(),
            'total_soal' => $examSet->questions()->count(),
            'ip_address' => request()->ip(),
        ]);
        
        return redirect()->route('murid.exams.work', $studentExam->id);
    }

    public function work(StudentExam $studentExam)
    {
        $murid = Auth::user();
        
        // Validasi kepemilikan
        if ($studentExam->user_id !== $murid->id) {
            return redirect()->route('murid.exams.index')
                ->with('error', 'Akses tidak diizinkan.');
        }
        
        // Cek apakah ujian sudah selesai
        if ($studentExam->status === 'selesai') {
            return redirect()->route('murid.exams.result', $studentExam->id);
        }
        
        // Cek timeout
        $startTime = strtotime($studentExam->started_at);
        $currentTime = time();
        $durationSeconds = $studentExam->examSet->durasi_menit * 60;
        $elapsedSeconds = $currentTime - $startTime;
        
        if ($elapsedSeconds > $durationSeconds) {
            // Selesaikan ujian secara otomatis
            return $this->finishExam($studentExam);
        }
        
        $studentExam->load('examSet.questions.options', 'answers');
        $remainingSeconds = $durationSeconds - $elapsedSeconds;
        
        return view('murid.exams.work', [
            'studentExam' => $studentExam,
            'remainingSeconds' => $remainingSeconds,
        ]);
    }

    public function submitAnswer(Request $request, StudentExam $studentExam)
    {
        $murid = Auth::user();
        
        if ($studentExam->user_id !== $murid->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        $validated = $request->validate([
            'question_id' => 'required|exists:questions,id',
            'answer_type' => 'required|in:pilihan_ganda,essay,benar_salah',
            'jawaban_dipilih' => 'nullable|exists:question_options,id',
            'jawaban_essay' => 'nullable|string',
        ]);
        
        $question = $studentExam->examSet->questions()->find($validated['question_id']);
        
        if (!$question) {
            return response()->json(['success' => false, 'message' => 'Soal tidak ditemukan'], 404);
        }
        
        // Cek jawaban
        $isCorrect = false;
        $nilaiDidapat = 0;
        
        if ($validated['answer_type'] === 'pilihan_ganda' || $validated['answer_type'] === 'benar_salah') {
            if ($validated['jawaban_dipilih']) {
                $selectedOption = $question->options()->find($validated['jawaban_dipilih']);
                $isCorrect = $selectedOption ? $selectedOption->is_correct : false;
                
                if ($isCorrect) {
                    $nilaiDidapat = $question->nilai_soal;
                }
            }
        } else if ($validated['answer_type'] === 'essay') {
            // Essay dijawab langsung, akan dinilai guru nanti
            $nilaiDidapat = 0;
        }
        
        // Simpan atau update jawaban
        $studentAnswer = $studentExam->answers()
            ->firstOrCreate(
                ['question_id' => $validated['question_id']],
                [
                    'jawaban_dipilih' => $validated['jawaban_dipilih'] ?? null,
                    'jawaban_essay' => $validated['jawaban_essay'] ?? null,
                    'is_correct' => $isCorrect,
                    'nilai_didapat' => $nilaiDidapat,
                ]
            );
        
        if ($studentAnswer->wasRecentlyCreated === false) {
            $studentAnswer->update([
                'jawaban_dipilih' => $validated['jawaban_dipilih'] ?? $studentAnswer->jawaban_dipilih,
                'jawaban_essay' => $validated['jawaban_essay'] ?? $studentAnswer->jawaban_essay,
                'is_correct' => $isCorrect,
                'nilai_didapat' => $nilaiDidapat,
            ]);
        }
        
        return response()->json(['success' => true, 'message' => 'Jawaban tersimpan']);
    }

    public function finishExam(StudentExam $studentExam)
    {
        $murid = Auth::user();
        
        if ($studentExam->user_id !== $murid->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        
        // Hitung nilai akhir
        $totalNilai = $studentExam->answers()->sum('nilai_didapat');
        $totalBenar = $studentExam->answers()->where('is_correct', true)->count();
        
        // Hitung persentase nilai
        $totalNilaiMax = $studentExam->examSet->questions()->sum('nilai_soal');
        $nilaiAkhir = $totalNilaiMax > 0 ? ($totalNilai / $totalNilaiMax) * 100 : 0;
        
        // Cek lulus atau tidak
        $lulus = $nilaiAkhir >= $studentExam->examSet->nilai_kkm;
        
        // Hitung durasi pengerjaan
        $durasiDetik = now()->diffInSeconds($studentExam->started_at);
        
        // Update student exam
        $studentExam->update([
            'status' => 'selesai',
            'finished_at' => now(),
            'nilai_akhir' => round($nilaiAkhir, 2),
            'total_benar' => $totalBenar,
            'lulus' => $lulus,
            'durasi_pengerjaan_detik' => $durasiDetik,
        ]);
        
        return redirect()->route('murid.exams.result', $studentExam->id)
            ->with('success', 'Ujian telah selesai.');
    }

    public function result(StudentExam $studentExam)
    {
        $murid = Auth::user();
        
        if ($studentExam->user_id !== $murid->id) {
            return redirect()->route('murid.exams.index')
                ->with('error', 'Akses tidak diizinkan.');
        }
        
        $studentExam->load('examSet.questions.options', 'answers.question.options', 'answers.selectedOption');
        
        return view('murid.exams.result', [
            'studentExam' => $studentExam,
        ]);
    }
}
