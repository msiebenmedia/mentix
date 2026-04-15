@extends('layouts.dashboard')

@section('title', 'Quiz steuern')

@section('content')
@php
    $statusClasses = [
        'draft' => $isPaused ? 'badge badge-warning' : 'badge badge-ghost',
        'scheduled' => 'badge badge-warning',
        'live' => 'badge badge-success',
        'ended' => 'badge badge-error',
    ];

    $isRevealed = (bool) data_get($quiz->settings, 'question_revealed', false);

    $sortedPlayers = $quiz->players
        ->sortByDesc(fn ($player) => (int) ($player->pivot->score ?? 0))
        ->values();

    $canStart = in_array($quiz->status, ['draft', 'scheduled'], true) && $totalQuestions > 0 && ! $isPaused;
    $canFinish = in_array($quiz->status, ['live', 'draft'], true);
    $canPause = $quiz->status === 'live' || ($quiz->status === 'draft' && $isPaused);
    $canNavigate = $totalQuestions > 0;
@endphp

<div class="min-h-screen bg-base-200 py-6 px-4">
    <div class="max-w-[1700px] mx-auto space-y-6">

        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Quiz steuern</h1>
                <p class="text-base-content/70 mt-1">
                    Host-Ansicht für Steuerung, Antworten und Live-Übersicht.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
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

        {{-- Obere Aktionsleiste --}}
        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body">
                <div class="flex flex-wrap gap-3">
                    @if($quiz->status === 'live' || ($quiz->status === 'draft' && $isPaused) || $quiz->status === 'ended')
                        <form method="POST" action="{{ route('admin.quizzes.finish', $quiz) }}" onsubmit="return confirm('Quiz wirklich beenden?');">
                            @csrf
                            <button type="submit" class="btn btn-dark btn-sm" {{ ! $canFinish ? 'disabled' : '' }}>
                                <i class="ti ti-player-stop"></i>
                                Quiz beenden
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.quizzes.start', $quiz) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm" {{ ! $canStart ? 'disabled' : '' }}>
                                <i class="ti ti-player-play"></i>
                                Quiz starten
                            </button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('admin.quizzes.pause', $quiz) }}">
                        @csrf
                        <button type="submit" class="btn btn-dark btn-sm" {{ ! $canPause ? 'disabled' : '' }}>
                            <i class="ti {{ $isPaused ? 'ti-player-play' : 'ti-player-pause' }}"></i>
                            {{ $isPaused ? 'Quiz fortführen' : 'Quiz pausieren' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.quizzes.previous-question', $quiz) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary" {{ ! $canNavigate || $currentIndex <= 0 ? 'disabled' : '' }}>
                            <i class="ti ti-arrow-left"></i>
                            Vorherige Frage
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.quizzes.next-question', $quiz) }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" {{ ! $canNavigate || $currentIndex >= ($totalQuestions - 1) ? 'disabled' : '' }}>
                            <i class="ti ti-arrow-right"></i>
                            Nächste Frage
                        </button>
                    </form>

                    <form method="POST" action="{{ route('admin.quizzes.reveal-question', $quiz) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm" {{ ! $currentQuestion || $isRevealed ? 'disabled' : '' }}>
                            <i class="ti ti-eye"></i>
                            Frage auflösen
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 2xl:grid-cols-12 gap-6">

            {{-- Linke Seite --}}
            <div class="2xl:col-span-8 space-y-6" id="host-live-left">

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        @if($currentQuestion)
                            <div class="flex flex-wrap items-center gap-2 mb-4">


                                <span class="badge badge-ghost">
                                    {{ $currentQuestion->points ?? 0 }} Punkte
                                </span>

                                <span class="badge badge-ghost">
                                    {{ $questionTypes[$currentQuestion->type] ?? $currentQuestion->type ?? '—' }}
                                </span>
                            </div>

                            <h2 class="text-3xl font-bold leading-snug">
                                {{ $currentQuestion->question }}
                            </h2>

                            @if($currentQuestion->image_path)
                                <div class="mt-5 rounded-2xl overflow-hidden border border-base-300 bg-base-200">
                                    <img
                                        src="{{ asset('storage/' . $currentQuestion->image_path) }}"
                                        alt="Fragenbild"
                                        class="w-full max-h-[420px] object-contain"
                                    >
                                </div>
                            @endif
                        @else
                            <div class="text-base-content/60">
                                Diesem Quiz sind aktuell keine Fragen zugewiesen.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="card-title text-xl">Antwortmöglichkeiten</h2>

                        </div>

                        @if($currentQuestion)
                            @if(in_array($currentQuestion->type, ['single_choice', 'true_false', 'image_choice', 'multiple_choice'], true))
                                <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
                                    @foreach($currentQuestion->options as $option)
                                        <div class="rounded-2xl border p-4 transition
                                            {{ $option->is_correct && $isRevealed ? 'border-success bg-success/10' : 'border-base-300 bg-base-100' }}">
                                            <div class="flex items-start justify-between gap-3">
                                                <div>
                                                    @if($option->label)
                                                        <div class="text-sm uppercase tracking-wide text-base-content/60">
                                                            {{ $option->label }}
                                                        </div>
                                                    @endif

                                                    <div class="text-lg font-semibold leading-snug">
                                                        {{ $option->option_text ?: '—' }}
                                                    </div>
                                                </div>

                                                @if($option->is_correct && $isRevealed)
                                                    <span class="badge badge-success">Richtig</span>
                                                @endif
                                            </div>

                                            @if($option->image_path)
                                                <div class="mt-4 rounded-xl overflow-hidden border border-base-300 bg-base-200">
                                                    <img
                                                        src="{{ asset('storage/' . $option->image_path) }}"
                                                        alt="Antwortbild"
                                                        class="w-full max-h-64 object-contain"
                                                    >
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif($currentQuestion->type === 'sorting')
                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($currentQuestion->options as $option)
                                        <div class="rounded-2xl border border-base-300 bg-base-100 p-4">
                                            <div class="text-sm uppercase tracking-wide text-base-content/60">
                                                {{ $option->label ?: 'Eintrag' }}
                                            </div>
                                            <div class="text-lg font-semibold">
                                                {{ $option->option_text ?: '—' }}
                                            </div>

                                            @if($isRevealed)
                                                <div class="mt-3 badge badge-success">
                                                    Reihenfolge: {{ $option->sort_order }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(in_array($currentQuestion->type, ['number', 'number_guess', 'numeric_guess', 'estimate'], true))
                                <div class="alert {{ $isRevealed ? 'alert-success' : 'alert-dark' }}">
                                    <span>
                                        {{ $isRevealed
                                            ? 'Richtige Antwort: ' . ($currentQuestion->correct_numeric_answer ?? 'Keine hinterlegt')
                                            : 'Zahlen-/Schätzfrage. Die Lösung wird nach dem Auflösen angezeigt.' }}
                                    </span>
                                </div>
                            @elseif(in_array($currentQuestion->type, ['date', 'date_guess'], true))
                                <div class="alert {{ $isRevealed ? 'alert-success' : 'alert-info' }}">
                                    <span>
                                        {{ $isRevealed
                                            ? 'Richtige Antwort: ' . ($currentQuestion->correct_date_answer?->format('d.m.Y') ?? 'Keine hinterlegt')
                                            : 'Datumsfrage. Die Lösung wird nach dem Auflösen angezeigt.' }}
                                    </span>
                                </div>
                            @elseif($currentQuestion->type === 'text')
                                <div class="alert {{ $isRevealed ? 'alert-success' : 'alert-info' }}">
                                    <span>
                                        {{ $isRevealed
                                            ? ($currentQuestion->explanation ?: 'Textantwort wurde aufgelöst.')
                                            : 'Textfrage. Die Antwort wird nach dem Auflösen sichtbar.' }}
                                    </span>
                                </div>
                            @else
                                <div class="text-base-content/60">
                                    Für diesen Fragetyp gibt es aktuell keine spezielle Darstellung.
                                </div>
                            @endif
                        @else
                            <div class="text-base-content/60">
                                Keine aktuelle Frage vorhanden.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="card-title text-xl">Antworten der Spieler</h2>
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
                                                : ($playerAnswer->option?->option_text ?? 'Antwort abgegeben');
                                        } elseif ($currentQuestion->type === 'multiple_choice') {
                                            $selectedOptions = $currentQuestion->options
                                                ->whereIn('id', collect($playerAnswer->answer_json ?? [])->map(fn ($id) => (int) $id)->all());

                                            $answerText = $selectedOptions->map(function ($option) {
                                                return $option->label
                                                    ? $option->label . ': ' . $option->option_text
                                                    : $option->option_text;
                                            })->implode(', ');
                                        } elseif ($currentQuestion->type === 'sorting') {
                                            $order = collect(data_get($playerAnswer->answer_json, 'order', []))
                                                ->map(fn ($id) => (int) $id)
                                                ->all();

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
                                            $answerText = $playerAnswer->answer_text ?: 'Textantwort abgegeben';
                                        } elseif (in_array($currentQuestion->type, ['date', 'date_guess'], true)) {
                                            $answerText = $playerAnswer->answer_date?->format('d.m.Y') ?: 'Datumsantwort abgegeben';
                                        } elseif (in_array($currentQuestion->type, ['number', 'number_guess', 'numeric_guess', 'estimate'], true)) {
                                            $answerText = $playerAnswer->answer_numeric ?? 'Zahl eingegeben';
                                        }
                                    }
                                @endphp

                                <div class="rounded-2xl border border-base-300 bg-base-100 px-4 py-4">
                                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="avatar placeholder">
                                                <div class="bg-primary text-primary-content rounded-full w-11">
                                                    <span class="font-semibold">
                                                        {{ strtoupper(substr($player->name ?? 'U', 0, 1)) }}
                                                    </span>
                                                </div>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="font-semibold truncate">{{ $player->name }}</div>
                                                <div class="text-sm text-base-content/70 break-words">
                                                    {{ $answerText }}
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            @if($playerAnswer)
                                                @if($isRevealed)
                                                    <span class="badge {{ $playerAnswer->is_correct ? 'badge-success' : 'badge-error' }}">
                                                        {{ $playerAnswer->is_correct ? 'Richtig' : 'Falsch' }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-info">
                                                        Abgegeben
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge badge-ghost">
                                                    Offen
                                                </span>
                                            @endif
                                        </div>
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

            {{-- Rechte Seite --}}
            <div class="2xl:col-span-4 space-y-6" id="host-live-right">

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="text-sm uppercase tracking-wide text-base-content/60 mb-2">
                            Fortschritt
                        </div>

                        <div class="text-3xl font-bold">
                            Frage {{ $totalQuestions > 0 ? $currentIndex + 1 : 0 }} von {{ $totalQuestions }}
                        </div>

                        <progress
                            class="progress progress-primary w-full mt-4"
                            value="{{ $totalQuestions > 0 ? $currentIndex + 1 : 0 }}"
                            max="{{ max($totalQuestions, 1) }}">
                        </progress>
                    </div>
                </div>

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="card-title">Scoreboard</h2>
                            <span class="badge badge-primary">{{ $playerCount }} Spieler</span>
                        </div>

                        <div class="space-y-3">
                            @forelse($sortedPlayers as $index => $player)
                                <div class="flex items-center justify-between gap-3 rounded-2xl border border-base-300 px-4 py-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-9 h-9 rounded-full bg-base-200 flex items-center justify-center font-bold">
                                            {{ $index + 1 }}
                                        </div>

                                        <div class="min-w-0">
                                            <div class="font-semibold truncate">{{ $player->name }}</div>
                                            @if(!empty($player->email))
                                                <div class="text-xs text-base-content/60 truncate">{{ $player->email }}</div>
                                            @endif
                                        </div>
                                    </div>

                                    <span class="badge badge-secondary badge-lg">
                                        {{ (int) ($player->pivot->score ?? 0) }} Pkt
                                    </span>
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
                        <div class="flex items-center justify-between gap-3 mb-4">
                            <h2 class="card-title">Fragenübersicht</h2>
                            <span class="badge badge-outline">{{ $totalQuestions }}</span>
                        </div>

                        <div class="max-h-[700px] overflow-y-auto pr-1 space-y-2">
                            @forelse($questions as $index => $question)
                                <div class="rounded-2xl border px-4 py-3 transition
                                    {{ $index === $currentIndex ? 'border-primary bg-primary/10' : 'border-base-300 bg-base-100' }}">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <div class="text-xs uppercase tracking-wide text-base-content/60 mb-1">
                                                Frage {{ $index + 1 }}
                                            </div>

                                            <div class="font-semibold leading-snug line-clamp-3">
                                                {{ $question->question }}
                                            </div>

                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <span class="badge badge-outline badge-sm">
                                                    {{ $question->points ?? 0 }} Pkt
                                                </span>

                                                <span class="badge badge-ghost badge-sm">
                                                    {{ $questionTypes[$question->type] ?? $question->type ?? '—' }}
                                                </span>
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

<script>
    (function () {
        let isRefreshing = false;

        async function refreshHostSections() {
            if (isRefreshing) return;
            isRefreshing = true;

            try {
                const response = await fetch(window.location.href, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    cache: 'no-store'
                });

                if (!response.ok) {
                    isRefreshing = false;
                    return;
                }

                const html = await response.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newLeft = doc.querySelector('#host-live-left');
                const newRight = doc.querySelector('#host-live-right');

                const currentLeft = document.querySelector('#host-live-left');
                const currentRight = document.querySelector('#host-live-right');

                if (newLeft && currentLeft) {
                    currentLeft.innerHTML = newLeft.innerHTML;
                }

                if (newRight && currentRight) {
                    currentRight.innerHTML = newRight.innerHTML;
                }
            } catch (error) {
                console.error('Host live refresh failed', error);
            }

            isRefreshing = false;
        }

        setInterval(refreshHostSections, 2000);
    })();
</script>
@endsection