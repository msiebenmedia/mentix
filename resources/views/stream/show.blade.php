<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $quiz->title }} - Stream</title>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/stream-show.js'])

    <style>
        html, body {
            margin: 0;
            padding: 0;
            background: transparent;
        }
    </style>
</head>
<body class="min-h-screen bg-transparent text-white overflow-hidden">
    <div class="min-h-screen flex items-center justify-center p-6 md:p-10">
        <div class="w-full max-w-6xl space-y-4">

            <div id="stream-paused-alert" class="hidden">
                <div class="alert alert-warning shadow-lg">
                    <span>Das Quiz ist aktuell pausiert.</span>
                </div>
            </div>

            <div id="stream-empty" class="hidden">
                <div class="rounded-3xl bg-base-100/80 backdrop-blur-md border border-white/10 shadow-2xl px-8 py-10 md:px-12 md:py-14 text-center">
                    <div class="text-2xl md:text-4xl font-bold text-base-content">
                        Aktuell ist keine Frage aktiv.
                    </div>
                </div>
            </div>

            <div id="stream-question-card" class="hidden">
                <div class="flex justify-center mb-5">
                    <div id="stream-type-badge" class="badge badge-primary badge-lg hidden"></div>
                </div>

                <div class="rounded-3xl bg-base-100/80 backdrop-blur-md border border-white/10 shadow-2xl px-8 py-10 md:px-12 md:py-14">
                    <div class="flex flex-col gap-5 text-center">
                        <div class="flex flex-wrap items-center justify-center gap-3 text-sm md:text-base">
                            <span id="stream-status-badge" class="badge badge-success"></span>
                            <span id="stream-progress-badge" class="badge badge-outline"></span>
                            <span id="stream-points-badge" class="badge badge-secondary hidden"></span>
                            <span id="stream-catalog-badge" class="badge badge-ghost hidden"></span>
                        </div>

                        <h1 id="stream-question-text" class="text-3xl md:text-5xl lg:text-6xl font-bold leading-tight text-base-content">
                            —
                        </h1>

                        <div id="stream-image-wrapper" class="hidden mt-4">
                            <div class="rounded-3xl overflow-hidden border border-white/10 bg-base-200">
                                <img
                                    id="stream-image"
                                    src=""
                                    alt="Fragenbild"
                                    class="w-full max-h-[500px] object-contain"
                                >
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <script>
        window.streamState = @json($streamState);
        window.quizQuestionTypes = @json($questionTypes);
        window.quizStatusLabels = @json(config('quiz.statuses', []));
        window.streamStateUrl = @json(route('stream.state', $quiz));
    </script>
</body>
</html>