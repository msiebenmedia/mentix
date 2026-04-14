<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Question;
use App\Models\QuestionCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type')->toString();
        $catalogId = $request->string('catalog')->toString();
        $status = $request->string('status')->toString();

        $questions = Question::query()
            ->with(['catalog'])
            ->when($search, function ($query) use ($search) {
                $query->where('question', 'like', '%' . $search . '%');
            })
            ->when($type !== '', function ($query) use ($type) {
                $query->where('type', $type);
            })
            ->when($catalogId !== '', function ($query) use ($catalogId) {
                $query->where('question_catalog_id', $catalogId);
            })
            ->when($status !== '', function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                }

                if ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $catalogs = QuestionCatalog::orderBy('title')->get();
        $types = Question::types();

        return view('admin.questions.index', compact('questions', 'catalogs', 'types'));
    }

    public function create()
    {
        $catalogs = QuestionCatalog::orderBy('title')->get();
        $types = Question::types();

        return view('admin.questions.create', compact('catalogs', 'types'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateQuestion($request);

        DB::transaction(function () use ($request, $validated) {
            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('questions', 'public');
            }

            $question = Question::create([
                'question_catalog_id' => $validated['question_catalog_id'],
                'type' => $validated['type'],
                'question' => $validated['question'],
                'image_path' => $imagePath,
                'correct_numeric_answer' => $validated['correct_numeric_answer'] ?? null,
                'correct_date_answer' => $validated['correct_date_answer'] ?? null,
                'explanation' => $validated['explanation'] ?? null,
                'points' => $validated['points'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $this->syncOptions($question, $request);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Frage wurde erstellt.');
    }

    public function show(Question $question)
    {
        $question->load(['catalog', 'options']);

        return view('admin.questions.show', compact('question'));
    }

    public function edit(Question $question)
    {
        $question->load('options');

        $catalogs = QuestionCatalog::orderBy('title')->get();
        $types = Question::types();

        return view('admin.questions.edit', compact('question', 'catalogs', 'types'));
    }

    public function update(Request $request, Question $question)
    {
        $validated = $this->validateQuestion($request, $question);

        DB::transaction(function () use ($request, $validated, $question) {
            $imagePath = $question->image_path;

            if ($request->hasFile('image')) {
                if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
                    Storage::disk('public')->delete($question->image_path);
                }

                $imagePath = $request->file('image')->store('questions', 'public');
            }

            if ($request->boolean('remove_image')) {
                if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
                    Storage::disk('public')->delete($question->image_path);
                }

                $imagePath = null;
            }

            $question->update([
                'question_catalog_id' => $validated['question_catalog_id'],
                'type' => $validated['type'],
                'question' => $validated['question'],
                'image_path' => $imagePath,
                'correct_numeric_answer' => $validated['correct_numeric_answer'] ?? null,
                'correct_date_answer' => $validated['correct_date_answer'] ?? null,
                'explanation' => $validated['explanation'] ?? null,
                'points' => $validated['points'],
                'is_active' => $request->boolean('is_active'),
            ]);

            $question->options()->delete();
            $this->syncOptions($question, $request);
        });

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Frage wurde aktualisiert.');
    }

    public function destroy(Question $question)
    {
        if ($question->image_path && Storage::disk('public')->exists($question->image_path)) {
            Storage::disk('public')->delete($question->image_path);
        }

        $question->delete();

        return redirect()
            ->route('admin.questions.index')
            ->with('success', 'Frage wurde gelöscht.');
    }

    protected function validateQuestion(Request $request, ?Question $question = null): array
    {
        $validated = $request->validate([
            'question_catalog_id' => ['required', 'exists:question_catalogs,id'],
            'type' => ['required', Rule::in(array_keys(Question::types()))],
            'question' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'correct_numeric_answer' => ['nullable', 'numeric'],
            'correct_date_answer' => ['nullable', 'date'],
            'explanation' => ['nullable', 'string'],
            'points' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],

            'options' => ['nullable', 'array'],
            'options.*.label' => ['nullable', 'string', 'max:10'],
            'options.*.option_text' => ['nullable', 'string', 'max:255'],
            'options.*.sort_order' => ['nullable', 'integer', 'min:1', 'max:4'],
            'correct_option' => ['nullable', 'integer'],
        ]);

        $type = $validated['type'];

        if (in_array($type, [Question::TYPE_SINGLE_CHOICE, Question::TYPE_IMAGE_CHOICE], true)) {
            $options = collect($request->input('options', []))
                ->filter(fn ($option) => filled($option['option_text'] ?? null))
                ->values();

            if ($options->count() !== 4) {
                throw ValidationException::withMessages([
                    'options' => 'Für Single Choice und Bildfragen müssen genau 4 Antwortmöglichkeiten gepflegt werden.',
                ]);
            }

            if (
                !$request->filled('correct_option') ||
                !in_array((int) $request->input('correct_option'), [0, 1, 2, 3], true)
            ) {
                throw ValidationException::withMessages([
                    'correct_option' => 'Bitte wähle genau eine richtige Antwort aus.',
                ]);
            }
        }

        if ($type === Question::TYPE_SORTING) {
            $options = collect($request->input('options', []))
                ->filter(fn ($option) => filled($option['option_text'] ?? null))
                ->values();

            if ($options->count() !== 4) {
                throw ValidationException::withMessages([
                    'options' => 'Für Sortierfragen müssen genau 4 Antwortmöglichkeiten gepflegt werden.',
                ]);
            }

            $sortOrders = collect($request->input('options', []))
                ->pluck('sort_order')
                ->filter(fn ($value) => filled($value))
                ->map(fn ($value) => (int) $value)
                ->values();

            if (
                $sortOrders->count() !== 4 ||
                $sortOrders->unique()->count() !== 4 ||
                $sortOrders->sort()->values()->all() !== [1, 2, 3, 4]
            ) {
                throw ValidationException::withMessages([
                    'options' => 'Für Sortierfragen muss jede Reihenfolge von 1 bis 4 genau einmal vergeben werden.',
                ]);
            }
        }

        if ($type === Question::TYPE_ESTIMATE && !$request->filled('correct_numeric_answer')) {
            throw ValidationException::withMessages([
                'correct_numeric_answer' => 'Für Schätzfragen muss eine korrekte Zahl eingetragen werden.',
            ]);
        }

        if ($type === Question::TYPE_DATE_GUESS && !$request->filled('correct_date_answer')) {
            throw ValidationException::withMessages([
                'correct_date_answer' => 'Für Datumfragen muss ein korrektes Datum eingetragen werden.',
            ]);
        }

        if ($type === Question::TYPE_IMAGE_CHOICE && !$question?->image_path && !$request->hasFile('image')) {
            throw ValidationException::withMessages([
                'image' => 'Für Bildfragen muss ein Bild hochgeladen werden.',
            ]);
        }

        return $validated;
    }

    protected function syncOptions(Question $question, Request $request): void
    {
        if (!$question->requiresOptions()) {
            return;
        }

        $options = collect($request->input('options', []));

        if ($question->type === Question::TYPE_SORTING) {
            foreach ($options as $index => $option) {
                if (!filled($option['option_text'] ?? null)) {
                    continue;
                }

                $question->options()->create([
                    'label' => $option['label'] ?? null,
                    'option_text' => $option['option_text'],
                    'sort_order' => (int) ($option['sort_order'] ?? ($index + 1)),
                    'is_correct' => false,
                ]);
            }

            return;
        }

        $correctOption = (int) $request->input('correct_option');

        foreach ($options as $index => $option) {
            if (!filled($option['option_text'] ?? null)) {
                continue;
            }

            $question->options()->create([
                'label' => $option['label'] ?? null,
                'option_text' => $option['option_text'],
                'sort_order' => $index + 1,
                'is_correct' => $correctOption === $index,
            ]);
        }
    }
}