<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="1">
    <title>{{ $quiz->title }} - Stream</title>

    <script>
        window.quizStreamState = @json($streamState);
        window.quizQuestionTypes = @json($questionTypes);
        window.quizStatusLabels = @json(config('quiz.statuses', []));
        window.quizStreamStateUrl = @json(route('stream.state', $quiz));
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: transparent;
        }
    </style>
</head>
<body class="min-h-screen bg-transparent text-white overflow-hidden">
    <div id="quiz-stream-root" class="min-h-screen flex items-center justify-center p-6 md:p-10">
        <div class="w-full max-w-6xl space-y-4">

            <div class="flex justify-center">
                <div class="flex flex-wrap items-center justify-center gap-3 text-sm md:text-base">
                    <span id="stream-status-badge" class="badge badge-success">
                        {{ $streamState['status_label'] ?? '—' }}
                    </span>

                    <span id="stream-progress-badge" class="badge badge-outline">
                        Frage {{ isset($streamState['current_index']) && $streamState['current_index'] !== null ? ($streamState['current_index'] + 1) : 0 }}/{{ $streamState['total_questions'] ?? 0 }}
                    </span>
                </div>
            </div>

            <div class="text-center space-y-3">
                <h1 id="stream-headline" class="text-3xl md:text-5xl lg:text-6xl font-bold text-base-content">
                    {{ !empty($streamState['question']) ? 'Aktuelle Frage' : 'Warte auf die nächste Frage' }}
                </h1>

                <p id="stream-subline" class="text-base-content/70 text-lg">
                    {{ $streamState['status_label'] ?? '—' }}
                </p>
            </div>

            <div id="stream-question-wrap" class="{{ !empty($streamState['question']) && empty($streamState['paused']) ? '' : 'hidden' }}">
                <div class="flex justify-center mb-5">
                    <div id="stream-question-type" class="badge badge-primary badge-lg">
                        {{ !empty($streamState['question']['type']) ? ($questionTypes[$streamState['question']['type']] ?? $streamState['question']['type']) : '' }}
                    </div>
                </div>

                <div class="rounded-3xl bg-base-100/80 backdrop-blur-md border border-white/10 shadow-2xl px-8 py-10 md:px-12 md:py-14">
                    <div class="flex flex-col gap-5 text-center">
                        <div id="stream-question-meta" class="text-sm md:text-base text-base-content/70">
                            @if(!empty($streamState['question']))
                                Frage {{ ($streamState['current_index'] ?? 0) + 1 }} von {{ $streamState['total_questions'] ?? 0 }}
                                • {{ $streamState['question']['points'] ?? 0 }} Punkte
                                @if(!empty($streamState['question']['catalog']))
                                    • {{ $streamState['question']['catalog'] }}
                                @endif
                            @endif
                        </div>

                        <h2 id="stream-question-text" class="text-3xl md:text-5xl lg:text-6xl font-bold leading-tight text-base-content">
                            {{ $streamState['question']['text'] ?? '—' }}
                        </h2>

                        <div id="stream-question-image-wrap" class="{{ !empty($streamState['question']['image_url']) ? '' : 'hidden' }} mt-4">
                            <div class="rounded-3xl overflow-hidden border border-white/10 bg-base-200">
                                <img
                                    id="stream-question-image"
                                    src="{{ $streamState['question']['image_url'] ?? '' }}"
                                    alt="Fragenbild"
                                    class="w-full max-h-[500px] object-contain"
                                >
                            </div>
                        </div>

                        <div id="stream-answers-wrap"></div>
                    </div>
                </div>
            </div>

            <div id="stream-reveal-wrap" class="hidden">
                <div id="stream-reveal-content"></div>
            </div>

            <div id="stream-leaderboard-wrap" class="{{ !empty($streamState['paused']) || (($streamState['status'] ?? null) === 'ended') ? '' : 'hidden' }}">
                <div class="card bg-base-100/80 backdrop-blur-md border border-white/10 shadow-2xl">
                    <div class="card-body">
                        <h2 class="card-title text-base-content">Leaderboard</h2>
                        <div id="stream-leaderboard-list"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>