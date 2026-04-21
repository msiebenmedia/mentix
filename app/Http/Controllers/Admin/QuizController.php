<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->toString();

        $quizzes = Quiz::query()
            ->with('creator')
            ->when($search, function ($query, $search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.quizzes.index', [
            'quizzes' => $quizzes,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.quizzes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'status' => Quiz::STATUS_DRAFT,
            'layout_template' => 'classic',
            'created_by' => Auth::id(),
            'current_question_index' => 0,
            'settings' => null,
        ]);

        return redirect()
            ->route('admin.quizzes.edit', $quiz)
            ->with('success', 'Quiz wurde erfolgreich erstellt.');
    }

    public function edit(Quiz $quiz): View
    {
        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
        ]);
    }

    public function update(Request $request, Quiz $quiz): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $quiz->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('admin.quizzes.edit', $quiz)
            ->with('success', 'Quiz wurde aktualisiert.');
    }

    public function destroy(Quiz $quiz): RedirectResponse
    {
        $quiz->delete();

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz wurde gelöscht.');
    }
}