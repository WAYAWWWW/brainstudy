<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\StudentsImport;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $guru = Auth::user();
        $kelas = $request->input('kelas');
        
        $students = User::where('guru_id', $guru->id)
            ->where('role', 'murid');
        
        if ($kelas) {
            $students = $students->where('kelas', $kelas);
        }
        
        $students = $students->with('studentExams')
            ->withCount('studentExams')
            ->paginate(10);

        $kelasOptions = User::where('guru_id', $guru->id)
            ->where('role', 'murid')
            ->distinct('kelas')
            ->pluck('kelas');

        return view('guru.students.index', [
            'students' => $students,
            'kelasOptions' => $kelasOptions,
        ]);
    }

    public function create()
    {
        return view('guru.students.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nisn' => 'required|string|size:10|unique:users',
            'kelas' => 'required|string|max:255',
            'email' => 'nullable|email|unique:users',
            'password' => 'nullable|string|min:6',
        ]);

        $validated['role'] = 'murid';
        $validated['guru_id'] = Auth::id();
        $validated['is_active'] = true;
        
        // Generate email otomatis jika kosong
        if (empty($validated['email'])) {
            $validated['email'] = $validated['nisn'] . '@brainstudy.id';
        }
        
        // Password default adalah NISN jika kosong
        if (empty($validated['password'])) {
            $validated['password'] = $validated['nisn'];
        }
        
        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('guru.students.index')
            ->with('success', 'Murid berhasil ditambahkan.');
    }

    public function edit(User $student)
    {
        $this->authorize('view', $student);
        return view('guru.students.edit', ['student' => $student]);
    }

    public function update(Request $request, User $student)
    {
        $this->authorize('update', $student);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'kelas' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $student->id,
        ]);

        $student->update($validated);

        return redirect()->route('guru.students.index')
            ->with('success', 'Data murid berhasil diperbarui.');
    }

    public function deactivate(User $student)
    {
        $this->authorize('update', $student);
        $student->update(['is_active' => false]);

        return back()->with('success', 'Murid berhasil dinonaktifkan.');
    }

    public function destroy(User $student)
    {
        $this->authorize('delete', $student);
        $student->delete();

        return back()->with('success', 'Murid berhasil dihapus.');
    }

    public function importTemplate()
    {
        return Excel::download(
            new \App\Exports\StudentTemplateExport(),
            'template_import_murid.xlsx'
        );
    }

    public function importForm()
    {
        return view('guru.students.import');
    }

    public function importProcess(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        try {
            Excel::import(new StudentsImport(Auth::id()), $request->file('file'));
            return redirect()->route('guru.students.index')
                ->with('success', 'Data murid berhasil diimpor.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimpor file: ' . $e->getMessage());
        }
    }
}
