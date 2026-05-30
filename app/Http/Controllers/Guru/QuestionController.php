<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\ExamSet;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class QuestionController extends Controller
{
    public function create(ExamSet $examSet)
    {
        $this->authorize('update', $examSet);
        $questions = $examSet->questions()->orderBy('urutan')->get();
        return view('guru.exam-sets.questions.create', [
            'examSet' => $examSet,
            'questions' => $questions,
        ]);
    }

    public function store(Request $request, ExamSet $examSet)
    {
        $this->authorize('update', $examSet);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pilihan_ganda,essay,benar_salah',
            'pertanyaan' => 'required|string',
            'gambar' => 'nullable|image|max:2048',
            'nilai_soal' => 'required|integer|min:1',
            'options' => 'nullable|array',
            'options.*.teks' => 'nullable|string',
            'options.*.gambar' => 'nullable|image|max:2048',
            'options.*.benar' => 'boolean',
            'jawaban_benar' => 'nullable|in:BENAR,SALAH',
            'kunci_jawaban' => 'nullable|string',
            'catatan_penilaian' => 'nullable|string',
        ]);

        // Upload gambar soal
        $imagePath = null;
        if ($request->hasFile('gambar')) {
            $imagePath = $request->file('gambar')->store('questions', 'public');
        }

        // Tentukan urutan soal
        $urutan = $examSet->questions()->max('urutan') + 1;

        $question = Question::create([
            'exam_set_id' => $examSet->id,
            'urutan' => $urutan,
            'tipe_soal' => $validated['tipe_soal'],
            'pertanyaan' => $validated['pertanyaan'],
            'gambar' => $imagePath,
            'nilai_soal' => $validated['nilai_soal'],
        ]);

        // Simpan options untuk pilihan ganda
        if ($validated['tipe_soal'] === 'pilihan_ganda' && $request->has('options')) {
            foreach ($request->input('options') as $index => $option) {
                if (!empty($option['teks'])) {
                    $optionImagePath = null;
                    if ($request->hasFile("options.$index.gambar")) {
                        $optionImagePath = $request->file("options.$index.gambar")->store('options', 'public');
                    }

                    QuestionOption::create([
                        'question_id' => $question->id,
                        'teks_opsi' => $option['teks'],
                        'gambar_opsi' => $optionImagePath,
                        'is_correct' => isset($option['benar']),
                        'urutan_opsi' => $index + 1,
                    ]);
                }
            }
        }

        // Simpan jawaban untuk benar/salah
        if ($validated['tipe_soal'] === 'benar_salah') {
            QuestionOption::create([
                'question_id' => $question->id,
                'teks_opsi' => 'BENAR',
                'is_correct' => $validated['jawaban_benar'] === 'BENAR',
                'urutan_opsi' => 1,
            ]);
            QuestionOption::create([
                'question_id' => $question->id,
                'teks_opsi' => 'SALAH',
                'is_correct' => $validated['jawaban_benar'] === 'SALAH',
                'urutan_opsi' => 2,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Soal berhasil ditambahkan.',
            'question' => $question,
        ]);
    }

    public function update(Request $request, ExamSet $examSet, Question $question)
    {
        $this->authorize('update', $examSet);

        $validated = $request->validate([
            'tipe_soal' => 'required|in:pilihan_ganda,essay,benar_salah',
            'pertanyaan' => 'required|string',
            'gambar' => 'nullable|image|max:2048',
            'nilai_soal' => 'required|integer|min:1',
            'options' => 'nullable|array',
        ]);

        // Update gambar jika ada
        if ($request->hasFile('gambar')) {
            if ($question->gambar) {
                Storage::disk('public')->delete($question->gambar);
            }
            $validated['gambar'] = $request->file('gambar')->store('questions', 'public');
        }

        $question->update([
            'pertanyaan' => $validated['pertanyaan'],
            'gambar' => $validated['gambar'] ?? $question->gambar,
            'nilai_soal' => $validated['nilai_soal'],
        ]);

        return back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question)
    {
        $examSet = $question->examSet;
        $this->authorize('update', $examSet);

        if ($question->gambar) {
            Storage::disk('public')->delete($question->gambar);
        }

        $question->delete();

        return back()->with('success', 'Soal berhasil dihapus.');
    }

    public function reorder(Request $request, ExamSet $examSet)
    {
        $this->authorize('update', $examSet);

        $order = $request->input('order', []);
        foreach ($order as $index => $questionId) {
            Question::where('id', $questionId)
                ->where('exam_set_id', $examSet->id)
                ->update(['urutan' => $index + 1]);
        }

        return response()->json(['success' => true]);
    }
}
