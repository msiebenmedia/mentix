<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class StreamController extends Controller
{
    public function show(Quiz $quiz): View
    {
        return view('stream.show', [
            'quiz' => $quiz,
            'streamState' => $this->buildStreamState($quiz),
            'questionTypes' => [
                'single_choice' => 'Single Choice',
                'multiple_choice' => 'Multiple Choice',
                'true_false' => 'Wahr / Falsch',
                'text' => 'Text',
                'number' => 'Zahl',
                'number_guess' => 'Zahl schätzen',
                'numeric_guess' => 'Zahl schätzen',
                'estimate' => 'Schätzfrage',
                'date' => 'Datum',
                'date_guess' => 'Datum schätzen',
                'image_choice' => 'Bildauswahl',
                'sorting' => 'Sortierfrage',
            ],
        ]);
    }

    public function state(Quiz $quiz): JsonResponse
    {
        return response()->json($this->buildStreamState($quiz));
    }

    protected function buildStreamState(Quiz $quiz): array
    {
        $quiz->load([
            'questions.catalog',
            'questions.options',
        ]);

        $questions = $quiz->questions->values();
        $currentIndex = max(0, (int) $quiz->current_question_index);
        $currentQuestion = $questions->get($currentIndex);
        $revealed = (bool) data_get($quiz->settings, 'question_revealed', false);
        $isPaused = $quiz->status === 'draft' && (bool) data_get($quiz->settings, 'quiz_paused', false);

        return [
            'quiz_id' => $quiz->id,
            'status' => $quiz->status,
            'status_label' => $isPaused
                ? 'Pausiert'
                : (config('quiz.statuses')[$quiz->status] ?? $quiz->status),
            'paused' => $isPaused,
            'current_index' => $currentQuestion ? $currentIndex : null,
            'total_questions' => $questions->count(),
            'revealed' => $revealed,
            'question' => $currentQuestion ? [
                'id' => $currentQuestion->id,
                'text' => $currentQuestion->question,
                'type' => $currentQuestion->type,
                'points' => $currentQuestion->points,
                'catalog' => $currentQuestion->catalog?->title,
                'image_url' => $currentQuestion->image_path
                    ? asset('storage/' . $currentQuestion->image_path)
                    : null,
            ] : null,
        ];
    }
}