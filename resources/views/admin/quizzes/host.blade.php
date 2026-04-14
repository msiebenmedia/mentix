@extends('layouts.dashboard')

@section('title', 'Quiz steuern')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-7xl mx-auto space-y-6">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Quiz steuern</h1>
                <p class="text-base-content/70 mt-1">
                    Steuere den Live-Ablauf deines Quiz direkt von hier aus.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-ghost">
                    <i class="ti ti-settings"></i>
                    Bearbeiten
                </a>

                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                    <i class="ti ti-arrow-left"></i>
                    Zurück
                </a>
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

        @php
            $statusClasses = [
                'draft' => $isPaused ? 'badge badge-warning' : 'badge badge-ghost',
                'scheduled' => 'badge badge-warning',
                'live' => 'badge badge-success',
                'ended' => 'badge badge-error',
            ];

            $isRevealed = (bool) data_get($quiz->settings, 'question_revealed', false);
        @endphp

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <div class="xl:col-span-2 space-y-6">

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h2 class="card-title text-2xl">{{ $quiz->title }}</h2>
                                <div class="flex flex-wrap gap-2 mt-3">
                                    <span class="badge badge-outline">
                                        {{ config('quiz.templates')[$quiz->layout_template] ?? $quiz->layout_template }}
                                    </span>
                                    <span class="badge badge-primary">{{ $playerCount }} Spieler</span>
                                    <span class="badge badge-secondary">{{ $totalQuestions }} Fragen</span>
                                    <span class="{{ $statusClasses[$quiz->status] ?? 'badge badge-ghost' }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-sm text-base-content/70">
                                Aktuelle Frage: {{ $totalQuestions > 0 ? ($currentIndex + 1) : 0 }}/{{ $totalQuestions }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('admin.quizzes.start', $quiz) }}">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="ti ti-player-play"></i>
                                    Starten
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.quizzes.pause', $quiz) }}">
                                @csrf
                                <button type="submit" class="btn btn-warning">
                                    <i class="ti {{ $isPaused ? 'ti-player-play' : 'ti-player-pause' }}"></i>
                                    {{ $isPaused ? 'Fortsetzen' : 'Pausieren' }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.quizzes.previous-question', $quiz) }}">
                                @csrf
                                <button type="submit" class="btn">
                                    <i class="ti ti-arrow-left"></i>
                                    Vorherige Frage
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.quizzes.next-question', $quiz) }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-arrow-right"></i>
                                    Nächste Frage
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.quizzes.reveal-question', $quiz) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary">
                                    <i class="ti ti-eye"></i>
                                    Auflösen
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.quizzes.finish', $quiz) }}" onsubmit="return confirm('Quiz wirklich beenden?');">
                                @csrf
                                <button type="submit" class="btn btn-error">
                                    <i class="ti ti-flag"></i>
                                    Beenden
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Aktuelle Frage</h2>

                        @if($currentQuestion)
                            <div class="space-y-4">
                                <div class="badge badge-outline">
                                    Frage {{ $currentIndex + 1 }} von {{ $totalQuestions }}
                                </div>

                                <div class="text-xl font-semibold leading-relaxed">
                                    {{ $currentQuestion->question }}
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span class="badge badge-primary">
                                        {{ $questionTypes[$currentQuestion->type] ?? $currentQuestion->type ?? '—' }}
                                    </span>

                                    <span class="badge badge-secondary">
                                        {{ $currentQuestion->points }} Punkte
                                    </span>

                                    @if($currentQuestion->catalog)
                                        <span class="badge badge-outline">
                                            {{ $currentQuestion->catalog->title }}
                                        </span>
                                    @endif
                                </div>

                                @if($currentQuestion->image_path)
                                    <div class="rounded-2xl overflow-hidden border border-base-300 bg-base-200">
                                        <img
                                            src="{{ asset('storage/' . $currentQuestion->image_path) }}"
                                            alt="Fragenbild"
                                            class="w-full max-h-[420px] object-contain"
                                        >
                                    </div>
                                @endif

                                <div class="divider">Antwortmöglichkeiten</div>

                                @if(in_array($currentQuestion->type, ['single_choice', 'true_false', 'image_choice', 'multiple_choice'], true))
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($currentQuestion->options as $option)
                                            <div class="border rounded-xl p-4 {{ $option->is_correct && $isRevealed ? 'border-success bg-success/10' : 'border-base-300' }}">
                                                @if($option->label)
                                                    <div class="font-semibold">{{ $option->label }}</div>
                                                @endif

                                                @if($option->option_text)
                                                    <div class="text-base-content/80">{{ $option->option_text }}</div>
                                                @endif

                                                @if($option->image_path)
                                                    <div class="mt-3 rounded-xl overflow-hidden border border-base-300 bg-base-200">
                                                        <img
                                                            src="{{ asset('storage/' . $option->image_path) }}"
                                                            alt="Antwortbild"
                                                            class="w-full max-h-56 object-contain"
                                                        >
                                                    </div>
                                                @endif

                                                @if($option->is_correct && $isRevealed)
                                                    <div class="mt-3 badge badge-success">Richtige Antwort</div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif($currentQuestion->type === 'sorting')
                                    <div class="space-y-3">
                                        @foreach($currentQuestion->options->sortBy('sort_order') as $index => $option)
                                            <div class="border rounded-xl p-4 {{ $isRevealed ? 'border-success bg-success/10' : 'border-base-300' }}">
                                                <div class="font-semibold">
                                                    {{ $index + 1 }}.
                                                    @if($option->label)
                                                        {{ $option->label }}
                                                    @endif
                                                </div>

                                                @if($option->option_text)
                                                    <div class="text-base-content/80">{{ $option->option_text }}</div>
                                                @endif

                                                @if($option->image_path)
                                                    <div class="mt-3 rounded-xl overflow-hidden border border-base-300 bg-base-200">
                                                        <img
                                                            src="{{ asset('storage/' . $option->image_path) }}"
                                                            alt="Sortierbild"
                                                            class="w-full max-h-56 object-contain"
                                                        >
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @elseif(in_array($currentQuestion->type, ['number', 'number_guess', 'numeric_guess', 'estimate'], true))
                                    <div class="alert {{ $isRevealed ? 'alert-success' : 'alert-info' }}">
                                        <span>
                                            {{ $isRevealed
                                                ? 'Richtige Antwort: ' . ($currentQuestion->correct_numeric_answer ?? 'Keine hinterlegt')
                                                : 'Schätzfrage / Zahlenfrage. Richtige Antwort wird erst nach dem Auflösen angezeigt.' }}
                                        </span>
                                    </div>
                                @elseif(in_array($currentQuestion->type, ['date', 'date_guess'], true))
                                    <div class="alert {{ $isRevealed ? 'alert-success' : 'alert-info' }}">
                                        <span>
                                            {{ $isRevealed
                                                ? 'Richtige Antwort: ' . ($currentQuestion->correct_date_answer?->format('d.m.Y') ?? 'Keine hinterlegt')
                                                : 'Datumsfrage. Richtige Antwort wird erst nach dem Auflösen angezeigt.' }}
                                        </span>
                                    </div>
                                @elseif($currentQuestion->type === 'text')
                                    <div class="alert alert-info">
                                        <span>
                                            {{ $isRevealed
                                                ? 'Textlösung anzeigen, falls im Datensatz vorhanden.'
                                                : 'Textfrage. Musterlösung wird erst nach dem Auflösen angezeigt.' }}
                                        </span>
                                    </div>
                                @endif

                                @if($isRevealed && !empty($currentQuestion->explanation))
                                    <div class="alert alert-info">
                                        <span>{{ $currentQuestion->explanation }}</span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-base-content/60">
                                Diesem Quiz sind aktuell keine Fragen zugewiesen.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="card-title">Antworten der Spieler</h2>
                            <span class="badge badge-outline">
                                {{ $currentAnswers->count() }}/{{ $quiz->players->count() }} beantwortet
                            </span>
                        </div>

                        <div class="space-y-3">
                            @forelse($quiz->players as $player)
                                @php
                                    $playerAnswer = $currentAnswers->firstWhere('user_id', $player->id);
                                    $answerText = 'Noch keine Antwort';

                                    if ($playerAnswer && $currentQuestion) {
                                        if (in_array($currentQuestion->type, ['single_choice', 'true_false', 'image_choice'], true)) {
                                            $answerText = $playerAnswer->option?->label
                                                ? $playerAnswer->option->label . ': ' . $playerAnswer->option->option_text
                                                : $playerAnswer->option?->option_text;
                                        } elseif ($currentQuestion->type === 'multiple_choice') {
                                            $selectedOptions = $currentQuestion->options
                                                ->whereIn('id', collect($playerAnswer->answer_json ?? [])->map(fn($id) => (int) $id)->all());

                                            $answerText = $selectedOptions->map(function ($option) {
                                                return $option->label
                                                    ? $option->label . ': ' . $option->option_text
                                                    : $option->option_text;
                                            })->implode(', ');
                                        } elseif ($currentQuestion->type === 'sorting') {
                                            $order = collect(data_get($playerAnswer->answer_json, 'order', []))->map(fn($id) => (int) $id)->all();

                                            $orderedOptions = collect($order)->map(function ($id) use ($currentQuestion) {
                                                return $currentQuestion->options->firstWhere('id', $id);
                                            })->filter();

                                            $answerText = $orderedOptions->map(function ($option, $index) {
                                                $text = $option->label
                                                    ? $option->label . ': ' . $option->option_text
                                                    : $option->option_text;

                                                return ($index + 1) . '. ' . $text;
                                            })->implode(' | ');
                                        } elseif ($currentQuestion->type === 'text') {
                                            $answerText = $playerAnswer->answer_text;
                                        } elseif (in_array($currentQuestion->type, ['date', 'date_guess'], true)) {
                                            $answerText = $playerAnswer->answer_date?->format('d.m.Y');
                                        } elseif (in_array($currentQuestion->type, ['number', 'number_guess', 'numeric_guess', 'estimate'], true)) {
                                            $answerText = $playerAnswer->answer_numeric;
                                        }
                                    }
                                @endphp

                                <div class="border border-base-300 rounded-xl px-4 py-3 flex flex-col gap-2">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="font-medium">{{ $player->name }}</div>

                                        @if($playerAnswer)
                                            <span class="badge {{ $playerAnswer->is_correct ? 'badge-success' : 'badge-error' }}">
                                                {{ $playerAnswer->is_correct ? 'Richtig' : 'Falsch' }}
                                            </span>
                                        @else
                                            <span class="badge badge-ghost">Offen</span>
                                        @endif
                                    </div>

                                    <div class="text-sm text-base-content/80">
                                        {{ $answerText }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-base-content/60">
                                    Keine Spieler zugewiesen.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            <div class="space-y-6">

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Spieler</h2>

                        <div class="space-y-3">
                            @forelse($quiz->players as $player)
                                <div class="flex items-center justify-between border border-base-300 rounded-xl px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-10">
                                                <span class="text-sm font-semibold">
                                                    {{ strtoupper(substr($player->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="font-medium truncate">{{ $player->name }}</div>
                                            @if(!empty($player->email))
                                                <div class="text-xs text-base-content/60 truncate">{{ $player->email }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <span class="badge badge-secondary">
                                            {{ $player->pivot->score ?? 0 }} Pkt
                                        </span>
                                    </div>
                                </div>
                            @empty
                                <div class="text-base-content/60">
                                    Keine Spieler zugewiesen.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <h2 class="card-title mb-4">Fragenübersicht</h2>

                        <div class="space-y-2 max-h-[500px] overflow-y-auto pr-1">
                            @forelse($questions as $index => $question)
                                <div class="border rounded-xl px-4 py-3 {{ $index === $currentIndex ? 'border-primary bg-base-200' : 'border-base-300' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-xs text-base-content/60 mb-1">
                                                Frage {{ $index + 1 }}
                                            </div>
                                            <div class="font-medium line-clamp-2">
                                                {{ $question->question }}
                                            </div>
                                        </div>

                                        @if($index === $currentIndex)
                                            <span class="badge badge-primary">Aktiv</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-base-content/60">
                                    Keine Fragen vorhanden.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection