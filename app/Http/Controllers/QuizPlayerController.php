<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuizPlayerController extends Controller
{
    public function show(Quiz $quiz): View
    {
        $quiz->load([
            'players',
            'questions.catalog',
            'questions.options',
        ]);

        $isPlayer = $quiz->players->contains('id', Auth::id());

        abort_unless($isPlayer, 403);

        $questions = $quiz->questions->values();
        $currentIndex = max(0, (int) $quiz->current_question_index);
        $currentQuestion = $questions->get($currentIndex);
        $isRevealed = (bool) data_get($quiz->settings, 'question_revealed', false);
        $isPaused = $quiz->status === 'draft' && (bool) data_get($quiz->settings, 'quiz_paused', false);

        $currentAnswer = null;

        if ($currentQuestion) {
            $currentAnswer = QuizAnswer::query()
                ->with('option')
                ->where('quiz_id', $quiz->id)
                ->where('question_id', $currentQuestion->id)
                ->where('user_id', Auth::id())
                ->first();
        }

        $leaderboard = $quiz->players
            ->sortByDesc(fn ($player) => (int) ($player->pivot->score ?? 0))
            ->values()
            ->map(function ($player, $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $player->id,
                    'name' => $player->name,
                    'score' => (int) ($player->pivot->score ?? 0),
                ];
            })
            ->all();

        return view('quizzes.play', [
            'quiz' => $quiz,
            'currentQuestion' => $currentQuestion,
            'currentIndex' => $currentQuestion ? $currentIndex : null,
            'totalQuestions' => $questions->count(),
            'isRevealed' => $isRevealed,
            'isPaused' => $isPaused,
            'leaderboard' => $leaderboard,
            'playerScore' => optional($quiz->players->firstWhere('id', Auth::id()))->pivot->score ?? 0,
            'currentAnswer' => $currentAnswer,
            'statusLabel' => $isPaused ? 'Pausiert' : (config('quiz.statuses')[$quiz->status] ?? $quiz->status),
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

    public function submitAnswer(Request $request, Quiz $quiz): RedirectResponse|JsonResponse
    {
        $quiz->load([
            'players',
            'questions.options',
        ]);

        $isPlayer = $quiz->players->contains('id', Auth::id());

        abort_unless($isPlayer, 403);

        if ($quiz->status !== 'live') {
            return $this->errorResponse($request, 'Das Quiz ist aktuell nicht live.', 422);
        }

        if ((bool) data_get($quiz->settings, 'question_revealed', false)) {
            return $this->errorResponse($request, 'Die aktuelle Frage wurde bereits aufgelöst.', 422);
        }

        $questions = $quiz->questions->values();
        $currentIndex = max(0, (int) $quiz->current_question_index);
        $currentQuestion = $questions->get($currentIndex);

        if (! $currentQuestion) {
            return $this->errorResponse($request, 'Aktuell ist keine Frage aktiv.', 422);
        }

        $existingAnswer = QuizAnswer::query()
            ->where('quiz_id', $quiz->id)
            ->where('question_id', $currentQuestion->id)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingAnswer) {
            return $this->errorResponse($request, 'Du hast diese Frage bereits beantwortet.', 422);
        }

        $rules = $this->rulesForQuestionType($currentQuestion->type);

        if (empty($rules)) {
            return $this->errorResponse($request, 'Dieser Fragetyp wird aktuell noch nicht unterstützt.', 422);
        }

        $validated = $request->validate($rules);

        $evaluation = $this->evaluateAnswer($currentQuestion, $validated);

        $answer = QuizAnswer::create([
            'quiz_id' => $quiz->id,
            'question_id' => $currentQuestion->id,
            'user_id' => Auth::id(),
            'question_option_id' => $evaluation['question_option_id'],
            'answer_text' => $evaluation['answer_text'],
            'answer_numeric' => $evaluation['answer_numeric'],
            'answer_date' => $evaluation['answer_date'],
            'answer_json' => $evaluation['answer_json'],
            'is_correct' => $evaluation['is_correct'],
            'points_awarded' => $evaluation['points_awarded'],
            'answered_at' => now(),
        ]);

        $player = $quiz->players->firstWhere('id', Auth::id());
        $currentScore = (int) ($player?->pivot?->score ?? 0);
        $newScore = $currentScore;

        if ($evaluation['points_awarded'] > 0) {
            $newScore = $currentScore + (int) $evaluation['points_awarded'];

            $quiz->players()->updateExistingPivot(Auth::id(), [
                'score' => $newScore,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Deine Antwort wurde gespeichert.',
                'has_answered' => true,
                'player_score' => $newScore,
                'player_answer' => $this->transformPlayerAnswer($answer),
            ]);
        }

        return redirect()
            ->route('quizzes.play', $quiz)
            ->with('success', 'Deine Antwort wurde gespeichert.');
    }

    protected function rulesForQuestionType(string $type): array
    {
        return match ($type) {
            'single_choice', 'true_false', 'image_choice' => [
                'question_option_id' => ['required', 'integer', 'exists:question_options,id'],
            ],
            'multiple_choice' => [
                'question_option_ids' => ['required', 'array', 'min:1'],
                'question_option_ids.*' => ['integer', 'distinct', 'exists:question_options,id'],
            ],
            'sorting' => [
                'question_option_ids' => ['required', 'array', 'min:1'],
                'question_option_ids.*' => ['integer', 'distinct', 'exists:question_options,id'],
            ],
            'text' => [
                'answer_text' => ['required', 'string', 'max:5000'],
            ],
            'date', 'date_guess' => [
                'answer_date' => ['required', 'date'],
            ],
            'number', 'number_guess', 'numeric_guess', 'estimate' => [
                'answer_numeric' => ['required', 'numeric'],
            ],
            default => [],
        };
    }

    protected function evaluateAnswer($question, array $validated): array
    {
        $result = [
            'question_option_id' => null,
            'answer_text' => null,
            'answer_numeric' => null,
            'answer_date' => null,
            'answer_json' => null,
            'is_correct' => false,
            'points_awarded' => 0,
        ];

        if (in_array($question->type, ['single_choice', 'true_false', 'image_choice'], true)) {
            $selectedOptionId = (int) $validated['question_option_id'];

            $option = $question->options->firstWhere('id', $selectedOptionId);

            if (! $option) {
                abort(422, 'Ungültige Antwortoption.');
            }

            $result['question_option_id'] = $option->id;
            $result['is_correct'] = (bool) $option->is_correct;
            $result['points_awarded'] = $result['is_correct'] ? (int) $question->points : 0;

            return $result;
        }

        if ($question->type === 'multiple_choice') {
            $selectedIds = collect($validated['question_option_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            $validOptionIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach ($selectedIds as $id) {
                if (! in_array($id, $validOptionIds, true)) {
                    abort(422, 'Ungültige Antwortoption.');
                }
            }

            $correctIds = $question->options
                ->where('is_correct', true)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values();

            $result['answer_json'] = $selectedIds->all();
            $result['is_correct'] = $selectedIds->values()->all() === $correctIds->values()->all();
            $result['points_awarded'] = $result['is_correct'] ? (int) $question->points : 0;

            return $result;
        }

        if ($question->type === 'sorting') {
            $selectedIds = collect($validated['question_option_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->values();

            $validOptionIds = $question->options->pluck('id')->map(fn ($id) => (int) $id)->all();

            foreach ($selectedIds as $id) {
                if (! in_array($id, $validOptionIds, true)) {
                    abort(422, 'Ungültige Antwortoption.');
                }
            }

            $correctOrder = $question->options
                ->sortBy('sort_order')
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $result['answer_json'] = [
                'order' => $selectedIds->all(),
            ];
            $result['is_correct'] = $selectedIds->all() === $correctOrder;
            $result['points_awarded'] = $result['is_correct'] ? (int) $question->points : 0;

            return $result;
        }

        if ($question->type === 'text') {
            $answerText = trim((string) ($validated['answer_text'] ?? ''));

            $possibleCorrectText = collect([
                $question->correct_text_answer ?? null,
                $question->correct_answer_text ?? null,
                $question->correct_answer ?? null,
            ])->filter()->first();

            $result['answer_text'] = $answerText;

            if ($possibleCorrectText !== null) {
                $result['is_correct'] = mb_strtolower($answerText) === mb_strtolower(trim((string) $possibleCorrectText));
                $result['points_awarded'] = $result['is_correct'] ? (int) $question->points : 0;
            }

            return $result;
        }

        if (in_array($question->type, ['date', 'date_guess'], true)) {
            $answerDate = date('Y-m-d', strtotime($validated['answer_date']));
            $correctDate = $question->correct_date_answer
                ? date('Y-m-d', strtotime($question->correct_date_answer))
                : null;

            $result['answer_date'] = $answerDate;
            $result['is_correct'] = $correctDate !== null && $answerDate === $correctDate;
            $result['points_awarded'] = $result['is_correct'] ? (int) $question->points : 0;

            return $result;
        }

        if (in_array($question->type, ['number', 'number_guess', 'numeric_guess', 'estimate'], true)) {
            $answerNumeric = (float) $validated['answer_numeric'];
            $correctNumeric = $question->correct_numeric_answer !== null
                ? (float) $question->correct_numeric_answer
                : null;

            $result['answer_numeric'] = $answerNumeric;
            $result['is_correct'] = $correctNumeric !== null && abs($answerNumeric - $correctNumeric) < 0.00001;
            $result['points_awarded'] = $result['is_correct'] ? (int) $question->points : 0;

            return $result;
        }

        return $result;
    }

    protected function transformPlayerAnswer(QuizAnswer $answer): array
    {
        return [
            'question_option_id' => $answer->question_option_id,
            'answer_text' => $answer->answer_text,
            'answer_numeric' => $answer->answer_numeric,
            'answer_date' => $answer->answer_date?->format('Y-m-d'),
            'answer_json' => $answer->answer_json,
            'is_correct' => (bool) $answer->is_correct,
        ];
    }

    protected function errorResponse(Request $request, string $message, int $status = 422): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message);
    }
}