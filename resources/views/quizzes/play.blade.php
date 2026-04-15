@extends('layouts.dashboard')

@section('title', 'Quiz spielen')

@section('content')
<div id="quiz-player-root" class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-5xl mx-auto space-y-6">

        @php
            $statusClasses = [
                'draft' => ($isPaused ?? false) ? 'badge badge-warning' : 'badge badge-ghost',
                'scheduled' => 'badge badge-warning',
                'live' => 'badge badge-success',
                'ended' => 'badge badge-error',
            ];

            $currentQuestion ??= null;
            $currentAnswer ??= null;
            $leaderboard ??= [];
            $playerScore ??= 0;
            $currentIndex ??= null;
            $totalQuestions ??= ($quiz->questions->count() ?? 0);
            $statusLabel ??= $quiz->status;
            $isPaused ??= false;
            $isRevealed ??= false;

            $initialState = [
                'quiz_id' => $quiz->id,
                'status' => $quiz->status,
                'status_label' => $statusLabel,
                'paused' => $isPaused,
                'current_index' => $currentIndex,
                'total_questions' => $totalQuestions,
                'revealed' => $isRevealed,
                'has_answered' => (bool) $currentAnswer,
                'player_score' => $playerScore,
                'leaderboard' => $leaderboard,
                'player_answer' => $currentAnswer ? [
                    'question_option_id' => $currentAnswer->question_option_id ?? null,
                    'answer_text' => $currentAnswer->answer_text ?? null,
                    'answer_numeric' => $currentAnswer->answer_numeric ?? null,
                    'answer_date' => $currentAnswer->answer_date?->format('Y-m-d'),
                    'answer_json' => $currentAnswer->answer_json ?? null,
                    'is_correct' => (bool) ($currentAnswer->is_correct ?? false),
                ] : null,
                'question' => $currentQuestion ? [
                    'id' => $currentQuestion->id ?? null,
                    'text' => $currentQuestion->question ?? '',
                    'type' => $currentQuestion->type ?? 'text',
                    'points' => $currentQuestion->points ?? 0,
                    'catalog' => $currentQuestion->catalog?->title ?? null,
                    'image_url' => !empty($currentQuestion->image_path)
                        ? asset('storage/' . $currentQuestion->image_path)
                        : null,
                    'options' => ($currentQuestion->options ?? collect())->map(function ($option) {
                        return [
                            'id' => $option->id ?? null,
                            'label' => $option->label ?? null,
                            'text' => $option->option_text ?? null,
                            'image_url' => !empty($option->image_path)
                                ? asset('storage/' . $option->image_path)
                                : null,
                            'is_correct' => (bool) ($option->is_correct ?? false),
                            'sort_order' => $option->sort_order ?? null,
                        ];
                    })->values()->all(),
                    'correct_numeric_answer' => $isRevealed ? ($currentQuestion->correct_numeric_answer ?? null) : null,
                    'correct_date_answer' => $isRevealed && $currentQuestion->correct_date_answer
                        ? $currentQuestion->correct_date_answer->format('Y-m-d')
                        : null,
                    'explanation' => $isRevealed ? ($currentQuestion->explanation ?? null) : null,
                ] : null,
            ];
        @endphp

        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">{{ $quiz->title }}</h1>
                <p class="text-base-content/70 mt-1">
                    Spieleransicht für das aktuelle Quiz.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <span id="quiz-status-badge" class="{{ $statusClasses[$quiz->status] ?? 'badge badge-ghost' }}">
                    {{ $statusLabel }}
                </span>

                <span class="badge badge-outline">
                    Layout: {{ config('quiz.templates')[$quiz->layout_template] ?? $quiz->layout_template }}
                </span>

                <span id="quiz-progress-badge" class="badge badge-secondary">
                    Frage {{ $currentQuestion && $currentIndex !== null ? ($currentIndex + 1) : 0 }}/{{ $totalQuestions }}
                </span>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error">
                <ul class="text-sm">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="quiz-status-alerts" class="space-y-3">
            @if($quiz->status === 'scheduled')
                <div class="alert alert-warning">
                    <span>Dieses Quiz ist geplant und noch nicht gestartet.</span>
                </div>
            @endif

            @if($isPaused)
                <div class="alert alert-warning">
                    <span>Das Quiz ist aktuell pausiert. Hier siehst du die aktuellen Punktestände.</span>
                </div>
            @elseif($quiz->status === 'draft')
                <div class="alert alert-info">
                    <span>Das Quiz ist aktuell noch nicht live.</span>
                </div>
            @endif

            @if($quiz->status === 'ended')
                <div class="alert alert-success">
                    <span>Dieses Quiz wurde bereits beendet.</span>
                </div>
            @endif
        </div>

        <div id="pause-leaderboard-card" class="card bg-base-100 shadow-xl border border-base-300 {{ $isPaused ? '' : 'hidden' }}">
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="card-title">Aktuelle Punktestände</h2>
                    <span class="badge badge-outline">Pause</span>
                </div>

                <div id="pause-leaderboard-list" class="space-y-3">
                    @forelse($leaderboard as $entry)
                        <div class="flex items-center justify-between border border-base-300 rounded-xl px-4 py-3">
                            <div class="flex items-center gap-3">
                                <span class="badge badge-primary">{{ $entry['rank'] }}</span>
                                <span class="font-medium">{{ $entry['name'] }}</span>
                            </div>
                            <span class="badge badge-secondary">{{ $entry['score'] }} Pkt</span>
                        </div>
                    @empty
                        <div class="text-base-content/60">Keine Punktestände verfügbar.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div id="question-card" class="card bg-base-100 shadow-xl border border-base-300 {{ $isPaused ? 'hidden' : '' }}">
            <div class="card-body">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                    <div>
                        <h2 class="card-title text-2xl">Aktuelle Frage</h2>
                        <p class="text-sm text-base-content/70">
                            Hier sieht der Spieler immer nur die momentan aktive Frage.
                        </p>
                    </div>

                    <span id="question-type-badge" class="badge badge-primary {{ $currentQuestion ? '' : 'hidden' }}">
                        {{ $currentQuestion ? (($questionTypes[$currentQuestion->type] ?? $currentQuestion->type)) : '' }}
                    </span>
                </div>

                <div id="question-empty" class="text-base-content/60 {{ $currentQuestion ? 'hidden' : '' }}">
                    Aktuell ist keine Frage aktiv.
                </div>

                <div id="question-content" class="space-y-5 {{ $currentQuestion ? '' : 'hidden' }}">
                    <div id="question-position" class="text-sm text-base-content/60">
                        @if($currentQuestion && $currentIndex !== null)
                            Frage {{ $currentIndex + 1 }} von {{ $totalQuestions }}
                        @endif
                    </div>

                    <div id="question-text" class="text-2xl font-semibold leading-relaxed">
                        {{ $currentQuestion?->question }}
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <span id="question-points" class="badge badge-secondary">
                            {{ $currentQuestion?->points ?? 0 }} Punkte
                        </span>

                        <span id="question-catalog" class="badge badge-outline {{ $currentQuestion?->catalog ? '' : 'hidden' }}">
                            {{ $currentQuestion?->catalog?->title }}
                        </span>
                    </div>

                    <div id="question-image-wrapper" class="{{ !empty($currentQuestion?->image_path) ? '' : 'hidden' }} rounded-2xl overflow-hidden border border-base-300 bg-base-200">
                        <img
                            id="question-image"
                            src="{{ !empty($currentQuestion?->image_path) ? asset('storage/' . $currentQuestion->image_path) : '' }}"
                            alt="Fragenbild"
                            class="w-full max-h-[420px] object-contain"
                        >
                    </div>

                    <div class="divider">Antwortbereich</div>

                    <div id="answer-form-container"></div>
                </div>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body">
                <h2 class="card-title mb-4">Dein Status</h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="border border-base-300 rounded-2xl p-4">
                        <div class="text-sm text-base-content/60 mb-1">Name</div>
                        <div class="font-semibold">{{ auth()->user()->name }}</div>
                    </div>

                    <div class="border border-base-300 rounded-2xl p-4">
                        <div class="text-sm text-base-content/60 mb-1">Punkte</div>
                        <div id="player-score-value" class="font-semibold">{{ $playerScore }}</div>
                    </div>

                    <div class="border border-base-300 rounded-2xl p-4">
                        <div class="text-sm text-base-content/60 mb-1">Quizstatus</div>
                        <div id="quiz-status-text" class="font-semibold">{{ $statusLabel }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    window.quizPlayerState = @json($initialState);
    window.quizStatusLabels = @json(config('quiz.statuses', []));
    window.quizQuestionTypes = @json($questionTypes ?? []);
    window.quizAnswerSubmitUrl = @json(route('quizzes.answer', $quiz));
    window.quizPlayerStateUrl = null;
    window.quizCsrfToken = @json(csrf_token());
</script>
@endsection