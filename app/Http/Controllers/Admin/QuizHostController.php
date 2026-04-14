<?php

namespace App\Http\Controllers\Admin;

use App\Events\QuizStateUpdated;
use App\Events\QuizStreamUpdated;
use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class QuizHostController extends Controller
{
    public function show(Quiz $quiz): View
    {
        $quiz->load([
            'players',
            'questions.catalog',
            'questions.options',
        ]);

        $questions = $quiz->questions->values();
        $currentIndex = max(0, (int) $quiz->current_question_index);
        $currentQuestion = $questions->get($currentIndex);
        $currentAnswers = collect();

        if ($currentQuestion) {
            $currentAnswers = QuizAnswer::query()
                ->with(['user', 'option'])
                ->where('quiz_id', $quiz->id)
                ->where('question_id', $currentQuestion->id)
                ->get();
        }

        $isPaused = $this->isPaused($quiz);

        return view('admin.quizzes.host', [
            'quiz' => $quiz,
            'questions' => $questions,
            'currentQuestion' => $currentQuestion,
            'currentIndex' => $currentIndex,
            'totalQuestions' => $questions->count(),
            'playerCount' => $quiz->players->count(),
            'currentAnswers' => $currentAnswers,
            'isPaused' => $isPaused,
            'statusLabel' => $this->statusLabel($quiz),
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

    public function start(Quiz $quiz): RedirectResponse
    {
        if ($quiz->questions()->count() === 0) {
            return redirect()
                ->route('admin.quizzes.host', $quiz)
                ->with('error', 'Diesem Quiz sind keine Fragen zugewiesen.');
        }

        $settings = $quiz->settings ?? [];
        $settings['question_revealed'] = false;
        $settings['quiz_paused'] = false;

        $quiz->update([
            'status' => 'live',
            'current_question_index' => 0,
            'settings' => $settings,
        ]);

        $this->broadcastState($quiz);

        return redirect()
            ->route('admin.quizzes.host', $quiz)
            ->with('success', 'Quiz wurde gestartet.');
    }

    public function pause(Quiz $quiz): RedirectResponse
    {
        $settings = $quiz->settings ?? [];
        $isPaused = $this->isPaused($quiz);

        if ($quiz->status === 'live' && ! $isPaused) {
            $settings['quiz_paused'] = true;

            $quiz->update([
                'status' => 'draft',
                'settings' => $settings,
            ]);

            $this->broadcastState($quiz);

            return redirect()
                ->route('admin.quizzes.host', $quiz)
                ->with('success', 'Quiz wurde pausiert.');
        }

        if ($quiz->status === 'draft' && $isPaused) {
            $settings['quiz_paused'] = false;

            $quiz->update([
                'status' => 'live',
                'settings' => $settings,
            ]);

            $this->broadcastState($quiz);

            return redirect()
                ->route('admin.quizzes.host', $quiz)
                ->with('success', 'Quiz wurde fortgesetzt.');
        }

        return redirect()
            ->route('admin.quizzes.host', $quiz)
            ->with('error', 'Das Quiz konnte in diesem Zustand nicht pausiert oder fortgesetzt werden.');
    }

    public function nextQuestion(Quiz $quiz): RedirectResponse
    {
        $questionsCount = $quiz->questions()->count();

        if ($questionsCount === 0) {
            return redirect()
                ->route('admin.quizzes.host', $quiz)
                ->with('error', 'Diesem Quiz sind keine Fragen zugewiesen.');
        }

        $nextIndex = min((int) $quiz->current_question_index + 1, $questionsCount - 1);

        $settings = $quiz->settings ?? [];
        $settings['question_revealed'] = false;

        $quiz->update([
            'current_question_index' => $nextIndex,
            'settings' => $settings,
        ]);

        $this->broadcastState($quiz);

        return redirect()
            ->route('admin.quizzes.host', $quiz)
            ->with('success', 'Zur nächsten Frage gewechselt.');
    }

    public function previousQuestion(Quiz $quiz): RedirectResponse
    {
        $previousIndex = max((int) $quiz->current_question_index - 1, 0);

        $settings = $quiz->settings ?? [];
        $settings['question_revealed'] = false;

        $quiz->update([
            'current_question_index' => $previousIndex,
            'settings' => $settings,
        ]);

        $this->broadcastState($quiz);

        return redirect()
            ->route('admin.quizzes.host', $quiz)
            ->with('success', 'Zur vorherigen Frage gewechselt.');
    }

    public function revealQuestion(Quiz $quiz): RedirectResponse
    {
        $settings = $quiz->settings ?? [];
        $settings['question_revealed'] = true;

        $quiz->update([
            'settings' => $settings,
        ]);

        $this->broadcastState($quiz);

        return redirect()
            ->route('admin.quizzes.host', $quiz)
            ->with('success', 'Frage wurde aufgelöst.');
    }

    public function finish(Quiz $quiz): RedirectResponse
    {
        $settings = $quiz->settings ?? [];
        $settings['quiz_paused'] = false;

        $quiz->update([
            'status' => 'ended',
            'ended_at' => now(),
            'settings' => $settings,
        ]);

        $this->broadcastState($quiz);

        return redirect()
            ->route('admin.quizzes.host', $quiz)
            ->with('success', 'Quiz wurde beendet.');
    }

    protected function broadcastState(Quiz $quiz): void
    {
        $quiz->load([
            'players',
            'questions.catalog',
            'questions.options',
        ]);

        $questions = $quiz->questions->values();
        $currentIndex = max(0, (int) $quiz->current_question_index);
        $currentQuestion = $questions->get($currentIndex);
        $revealed = (bool) data_get($quiz->settings, 'question_revealed', false);
        $isPaused = $this->isPaused($quiz);

        $playerPayload = $this->buildPlayerPayload(
            $quiz,
            $questions,
            $currentQuestion,
            $currentIndex,
            $revealed,
            $isPaused
        );

        $streamPayload = $this->buildStreamPayload(
            $quiz,
            $questions,
            $currentQuestion,
            $currentIndex,
            $revealed,
            $isPaused
        );

        event(new QuizStateUpdated($quiz, $playerPayload));
        event(new QuizStreamUpdated($quiz, $streamPayload));
    }

    protected function buildPlayerPayload(
        Quiz $quiz,
        Collection $questions,
        $currentQuestion,
        int $currentIndex,
        bool $revealed,
        bool $isPaused
    ): array {
        return [
            'quiz_id' => $quiz->id,
            'status' => $quiz->status,
            'status_label' => $this->statusLabel($quiz),
            'paused' => $isPaused,
            'current_index' => $currentQuestion ? $currentIndex : null,
            'total_questions' => $questions->count(),
            'revealed' => $revealed,
            'leaderboard' => $this->leaderboard($quiz),
            'question' => $currentQuestion ? [
                'id' => $currentQuestion->id,
                'text' => $currentQuestion->question,
                'type' => $currentQuestion->type,
                'points' => $currentQuestion->points,
                'catalog' => $currentQuestion->catalog?->title,
                'image_url' => $currentQuestion->image_path
                    ? asset('storage/' . $currentQuestion->image_path)
                    : null,
                'options' => $currentQuestion->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'label' => $option->label,
                        'text' => $option->option_text,
                        'image_url' => $option->image_path
                            ? asset('storage/' . $option->image_path)
                            : null,
                        'is_correct' => (bool) $option->is_correct,
                        'sort_order' => $option->sort_order,
                    ];
                })->values()->all(),
                'correct_numeric_answer' => $revealed ? $currentQuestion->correct_numeric_answer : null,
                'correct_date_answer' => $revealed && $currentQuestion->correct_date_answer
                    ? $currentQuestion->correct_date_answer->format('Y-m-d')
                    : null,
                'explanation' => $revealed ? $currentQuestion->explanation : null,
            ] : null,
        ];
    }

    protected function buildStreamPayload(
        Quiz $quiz,
        Collection $questions,
        $currentQuestion,
        int $currentIndex,
        bool $revealed,
        bool $isPaused
    ): array {
        return [
            'quiz_id' => $quiz->id,
            'status' => $quiz->status,
            'status_label' => $this->statusLabel($quiz),
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

    protected function isPaused(Quiz $quiz): bool
    {
        return $quiz->status === 'draft' && (bool) data_get($quiz->settings, 'quiz_paused', false);
    }

    protected function statusLabel(Quiz $quiz): string
    {
        if ($this->isPaused($quiz)) {
            return 'Pausiert';
        }

        return config('quiz.statuses')[$quiz->status] ?? $quiz->status;
    }

    protected function leaderboard(Quiz $quiz): array
    {
        return $quiz->players
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
    }
}