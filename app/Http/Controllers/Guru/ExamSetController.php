<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ExamSetController extends Controller
{
    public function index()
    {
        $guru = Auth::user();
        $examSets = ExamSet::where('guru_id', $guru->id)
            ->with('questions')
            ->withCount('studentExams')
            ->latest()
            ->paginate(10);

        foreach ($examSets as $exam) {
            $exam->avg_nilai = $exam->studentExams->whereNotNull('nilai_akhir')->avg('nilai_akhir') ?? 0;
        }

        return view('guru.exam-sets.index', ['examSets' => $examSets]);
    }

    public function create()
    {
        return view('guru.exam-sets.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'mata_pelajaran' => 'required|string|max:255',
            'kelas_target' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'durasi_menit' => 'required|integer|min:1',
            'nilai_kkm' => 'required|integer|min:0|max:100',
            'tanggal_mulai' => 'nullable|datetime',
            'tanggal_selesai' => 'nullable|datetime',
            'acak_soal' => 'boolean',
            'acak_jawaban' => 'boolean',
            'max_attempt' => 'required|integer|min:1',
        ]);

        $validated['guru_id'] = Auth::id();
        $validated['token'] = strtoupper(Str::random(6));
        $validated['acak_soal'] = $request->has('acak_soal');
        $validated['acak_jawaban'] = $request->has('acak_jawaban');

        $examSet = ExamSet::create($validated);

        return redirect()->route('guru.exam-sets.questions.create', $examSet->id)
            ->with('success', 'Paket soal berhasil dibuat. Mulai tambahkan soal.');
    }

    public function edit(ExamSet $examSet)
    {
        $this->authorize('update', $examSet);
        return view('guru.exam-sets.edit', ['examSet' => $examSet]);
    }

    public function update(Request $request, ExamSet $examSet)
    {
        $this->authorize('update', $examSet);

        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'mata_pelajaran' => 'required|string|max:255',
            'kelas_target' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'durasi_menit' => 'required|integer|min:1',
            'nilai_kkm' => 'required|integer|min:0|max:100',
            'tanggal_mulai' => 'nullable|datetime',
            'tanggal_selesai' => 'nullable|datetime',
            'acak_soal' => 'boolean',
            'acak_jawaban' => 'boolean',
            'max_attempt' => 'required|integer|min:1',
            'status' => 'in:draft,aktif,nonaktif,selesai',
        ]);

        $validated['acak_soal'] = $request->has('acak_soal');
        $validated['acak_jawaban'] = $request->has('acak_jawaban');

        $examSet->update($validated);

        return redirect()->route('guru.exam-sets.index')
            ->with('success', 'Paket soal berhasil diperbarui.');
    }

    public function show(ExamSet $examSet)
    {
        $this->authorize('view', $examSet);
        $examSet->load('questions.options', 'studentExams.user');
        return view('guru.exam-sets.show', ['examSet' => $examSet]);
    }

    public function destroy(ExamSet $examSet)
    {
        $this->authorize('delete', $examSet);
        $examSet->delete();
        return redirect()->route('guru.exam-sets.index')
            ->with('success', 'Paket soal berhasil dihapus.');
    }

    public function regenerateToken(ExamSet $examSet)
    {
        $this->authorize('update', $examSet);
        $examSet->update(['token' => strtoupper(Str::random(6))]);
        return back()->with('success', 'Token berhasil diperbarui.');
    }

    public function duplicate(ExamSet $examSet)
    {
        $this->authorize('view', $examSet);
        
        $newExam = $examSet->replicate();
        $newExam->token = strtoupper(Str::random(6));
        $newExam->status = 'draft';
        $newExam->save();

        // Duplicate questions and options
        foreach ($examSet->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->exam_set_id = $newExam->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }

        return redirect()->route('guru.exam-sets.edit', $newExam->id)
            ->with('success', 'Paket soal berhasil diduplikasi.');
    }
}
