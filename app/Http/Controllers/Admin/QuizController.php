<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionCatalog;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuizController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status')->toString();

        $quizzes = Quiz::query()
            ->with(['creator', 'players'])
            ->when($search, function ($query, $search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.quizzes.index', [
            'quizzes' => $quizzes,
            'statuses' => config('quiz.statuses', []),
            'templates' => config('quiz.templates', []),
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create()
    {
        $players = User::query()
            ->whereKeyNot(Auth::id())
            ->orderBy('name')
            ->get();

        return view('admin.quizzes.create', [
            'players' => $players,
            'templates' => config('quiz.templates', []),
            'statuses' => config('quiz.statuses', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(config('quiz.statuses', [])))],
            'layout_template' => ['required', Rule::in(array_keys(config('quiz.templates', [])))],
            'starts_at' => ['nullable', 'date'],
            'players' => ['required', 'array', 'min:1'],
            'players.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        if ($validated['status'] === 'scheduled' && empty($validated['starts_at'])) {
            return back()
                ->withErrors([
                    'starts_at' => 'Bitte wähle eine Startzeit, wenn das Quiz geplant ist.',
                ])
                ->withInput();
        }

        $quiz = Quiz::create([
            'title' => $validated['title'],
            'status' => $validated['status'],
            'layout_template' => $validated['layout_template'],
            'starts_at' => $validated['starts_at'] ?? null,
            'created_by' => Auth::id(),
            'current_question_index' => 0,
            'settings' => [
                'question_revealed' => false,
                'quiz_paused' => false,
            ],
        ]);

        $quiz->players()->sync($validated['players']);

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz wurde erfolgreich erstellt.');
    }

    public function show(Quiz $quiz)
    {
        return redirect()->route('admin.quizzes.edit', $quiz);
    }

    public function edit(Request $request, Quiz $quiz)
    {
        $quiz->load([
            'players',
            'questions.catalog',
        ]);

        $players = User::query()
            ->whereKeyNot(Auth::id())
            ->orderBy('name')
            ->get();

        $questionSearch = $request->string('question_search')->toString();
        $questionCatalog = $request->string('question_catalog')->toString();
        $questionType = $request->string('question_type')->toString();
        $questionStatus = $request->string('question_status')->toString();

        $availableQuestions = Question::query()
            ->with('catalog')
            ->when($questionSearch, function ($query, $questionSearch) {
                $query->where('question', 'like', '%' . $questionSearch . '%');
            })
            ->when($questionCatalog !== '', function ($query) use ($questionCatalog) {
                $query->where('question_catalog_id', $questionCatalog);
            })
            ->when($questionType !== '', function ($query) use ($questionType) {
                $query->where('type', $questionType);
            })
            ->when($questionStatus !== '', function ($query) use ($questionStatus) {
                if ($questionStatus === 'active') {
                    $query->where('is_active', true);
                }

                if ($questionStatus === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderByDesc('id')
            ->get();

        return view('admin.quizzes.edit', [
            'quiz' => $quiz,
            'players' => $players,
            'templates' => config('quiz.templates', []),
            'statuses' => config('quiz.statuses', []),
            'catalogs' => QuestionCatalog::query()->orderBy('title')->get(),
            'availableQuestions' => $availableQuestions,
            'questionTypes' => [
                'single_choice' => 'Single Choice',
                'multiple_choice' => 'Multiple Choice',
                'true_false' => 'Wahr / Falsch',
                'text' => 'Text',
                'number' => 'Zahl',
                'date' => 'Datum',
                'image_choice' => 'Bildauswahl',
            ],
        ]);
    }

    public function update(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(config('quiz.statuses', [])))],
            'layout_template' => ['required', Rule::in(array_keys(config('quiz.templates', [])))],
            'starts_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'players' => ['required', 'array', 'min:1'],
            'players.*' => ['integer', 'distinct', 'exists:users,id'],
            'tab' => ['nullable', 'string'],
        ]);

        if ($validated['status'] === 'scheduled' && empty($validated['starts_at'])) {
            return back()
                ->withErrors([
                    'starts_at' => 'Bitte wähle eine Startzeit, wenn das Quiz geplant ist.',
                ])
                ->withInput();
        }

        $quiz->update([
            'title' => $validated['title'],
            'status' => $validated['status'],
            'layout_template' => $validated['layout_template'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ended_at' => $validated['ended_at'] ?? null,
        ]);

        $quiz->players()->sync($validated['players']);

        return redirect()
            ->route('admin.quizzes.edit', [
                'quiz' => $quiz->id,
                'tab' => 'settings',
            ])
            ->with('success', 'Quiz wurde erfolgreich aktualisiert.');
    }

    public function updateQuestions(Request $request, Quiz $quiz)
    {
        $validated = $request->validate([
            'question_ids' => ['nullable', 'array'],
            'question_ids.*' => ['integer', 'distinct', 'exists:questions,id'],
        ]);

        $questionIds = collect($validated['question_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->values();

        $syncData = [];

        foreach ($questionIds as $index => $questionId) {
            $syncData[$questionId] = [
                'sort_order' => $index + 1,
            ];
        }

        $quiz->questions()->sync($syncData);

        return redirect()
            ->route('admin.quizzes.edit', [
                'quiz' => $quiz->id,
                'tab' => 'questions',
            ])
            ->with('success', 'Fragen wurden erfolgreich dem Quiz zugewiesen.');
    }

    public function restart(Quiz $quiz)
    {
        $quiz->load('players');

        DB::transaction(function () use ($quiz) {
            if (method_exists($quiz, 'answers')) {
                $quiz->answers()->delete();
            }

            $quiz->update([
                'status' => 'draft',
                'starts_at' => null,
                'ended_at' => null,
                'current_question_index' => 0,
                'settings' => [
                    'question_revealed' => false,
                    'quiz_paused' => false,
                ],
            ]);

            foreach ($quiz->players as $player) {
                $quiz->players()->updateExistingPivot($player->id, [
                    'score' => 0,
                    'joined_at' => null,
                    'finished_at' => null,
                ]);
            }
        });

        return redirect()
            ->route('admin.quizzes.edit', [
                'quiz' => $quiz->id,
                'tab' => 'settings',
            ])
            ->with('success', 'Quiz wurde erfolgreich neu gestartet.');
    }

    public function destroy(Quiz $quiz)
    {
        $quiz->delete();

        return redirect()
            ->route('admin.quizzes.index')
            ->with('success', 'Quiz wurde erfolgreich gelöscht.');
    }
    public function shuffleQuestions(Quiz $quiz)
{
    $questionIds = $quiz->questions()
        ->pluck('questions.id')
        ->shuffle()
        ->values();

    $syncData = [];

    foreach ($questionIds as $index => $questionId) {
        $syncData[$questionId] = [
            'sort_order' => $index + 1,
        ];
    }

    $quiz->questions()->sync($syncData);

    return redirect()
        ->route('admin.quizzes.edit', [
            'quiz' => $quiz->id,
            'tab' => 'questions',
        ])
        ->with('success', 'Fragen wurden zufällig neu sortiert.');
}
}