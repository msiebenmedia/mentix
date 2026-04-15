@extends('layouts.dashboard')

@section('title', 'Quiz spielen')

@section('content')
<div id="quiz-player-root" class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-5xl mx-auto space-y-6">

        @php
            $statusClasses = [
                'draft' => $isPaused ?? false ? 'badge badge-warning' : 'badge badge-ghost',
                'scheduled' => 'badge badge-warning',
                'live' => 'badge badge-success',
                'ended' => 'badge badge-error',
            ];

            $currentQuestion ??= null;
            $currentAnswer ??= null;
            $leaderboard ??= [];
            $playerScore ??= 0;
            $currentIndex ??= 0;
            $totalQuestions ??= $quiz->questions->count() ?? 0;
            $statusLabel ??= $quiz->status;

            $initialState = [
                'quiz_id' => $quiz->id,
                'status' => $quiz->status,
                'status_label' => $statusLabel,
                'paused' => $isPaused ?? false,
                'current_index' => $currentIndex,
                'total_questions' => $totalQuestions,
                'revealed' => $isRevealed ?? false,
                'has_answered' => (bool) $currentAnswer,
                'player_score' => $playerScore,
                'leaderboard' => $leaderboard,
                'player_answer' => $currentAnswer ? [
                    'question_option_id' => $currentAnswer->question_option_id ?? null,
                    'answer_text' => $currentAnswer->answer_text ?? null,
                    'answer_numeric' => $currentAnswer->answer_numeric ?? null,
                    'answer_date' => $currentAnswer->answer_date?->format('Y-m-d') ?? null,
                    'answer_json' => $currentAnswer->answer_json ?? null,
                    'is_correct' => (bool) ($currentAnswer->is_correct ?? false),
                ] : null,
                'question' => $currentQuestion ? [
                    'id' => $currentQuestion->id ?? null,
                    'text' => $currentQuestion->question ?? '',
                    'type' => $currentQuestion->type ?? 'text',
                    'points' => $currentQuestion->points ?? 0,
                    'catalog' => $currentQuestion->catalog?->title ?? null,
                    'image_url' => !empty($currentQuestion->image_path) ? asset('storage/' . $currentQuestion->image_path) : null,
                    'options' => $currentQuestion->options->map(function ($option) {
                        return [
                            'id' => $option->id ?? null,
                            'label' => $option->label ?? null,
                            'text' => $option->option_text ?? null,
                            'image_url' => !empty($option->image_path) ? asset('storage/' . $option->image_path) : null,
                            'is_correct' => (bool) ($option->is_correct ?? false),
                            'sort_order' => $option->sort_order ?? null,
                        ];
                    })->values()->all(),
                    'correct_numeric_answer' => $isRevealed ? $currentQuestion->correct_numeric_answer ?? null : null,
                    'correct_date_answer' => $isRevealed && $currentQuestion->correct_date_answer
                        ? $currentQuestion->correct_date_answer->format('Y-m-d')
                        : null,
                    'explanation' => $isRevealed ? $currentQuestion->explanation ?? null : null,
                ] : null,
            ];
        @endphp

        {{-- ... der Rest bleibt unverändert, nur IDs und Platzhalter wie vorher ... --}}

    </div>
</div>

<script>
    window.quizPlayerState = @json($initialState);
    window.quizStatusLabels = @json(config('quiz.statuses', []));
    window.quizQuestionTypes = @json($questionTypes ?? []);
    window.quizAnswerSubmitUrl = @json(route('quizzes.answer', $quiz));

    // Optional: Fallback JSON-State-Endpunkt
    window.quizPlayerStateUrl = null;

    window.quizCsrfToken = @json(csrf_token());
</script>
@endsection