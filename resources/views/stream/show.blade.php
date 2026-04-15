<!DOCTYPE html>
<html lang="de" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        body {
            min-height: 100vh;
        }

        .stream-shell {
            min-height: 100vh;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .glass-panel {
            background: rgba(20, 20, 28, 0.72);
            border: 1px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            box-shadow: 0 20px 80px rgba(0,0,0,0.35);
        }

        .question-area {
            flex: 1 1 auto;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 18px;
            min-height: 0;
        }

        .players-area {
            flex: 0 0 auto;
        }

        .player-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        @media (min-width: 1024px) {
            .player-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .player-card {
            transition: transform 180ms ease, border-color 180ms ease, background 180ms ease;
        }

        .player-card.top-player {
            border-color: rgba(255, 215, 0, 0.35);
            background: rgba(255, 215, 0, 0.08);
        }

        .answer-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 14px;
        }

        @media (min-width: 768px) {
            .answer-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .answer-card.correct {
            border-color: rgba(34,197,94,0.45);
            background: rgba(34,197,94,0.12);
        }

        .answer-card.wrong {
            border-color: rgba(239,68,68,0.45);
            background: rgba(239,68,68,0.10);
            opacity: 0.85;
        }

        .reveal-highlight {
            border-color: rgba(34,197,94,0.45);
            background: rgba(34,197,94,0.12);
        }

        .hidden-soft {
            display: none !important;
        }
    </style>
</head>
<body class="bg-transparent text-white overflow-hidden">
    <div id="quiz-stream-root" class="stream-shell">

        <div class="flex flex-wrap items-center justify-center gap-3 text-sm md:text-base">
            <span id="stream-status-badge" class="badge badge-success badge-lg">
                {{ $streamState['status_label'] ?? '—' }}
            </span>

            <span id="stream-progress-badge" class="badge badge-outline badge-lg">
                Frage {{ isset($streamState['current_index']) && $streamState['current_index'] !== null ? ($streamState['current_index'] + 1) : 0 }}/{{ $streamState['total_questions'] ?? 0 }}
            </span>

            <span id="stream-phase-badge" class="badge badge-primary badge-lg">
                {{ !empty($streamState['revealed']) ? 'Auflösung' : 'Frage läuft' }}
            </span>
        </div>

        <div class="question-area">
            <div class="glass-panel rounded-3xl px-8 py-8 md:px-12 md:py-10">
                <div class="text-center space-y-5">
                    <div class="flex justify-center">
                        <div id="stream-question-type" class="badge badge-primary badge-lg">
                            {{ !empty($streamState['question']['type']) ? ($questionTypes[$streamState['question']['type']] ?? $streamState['question']['type']) : '' }}
                        </div>
                    </div>

                    <div class="space-y-2">
                        <h1 id="stream-headline" class="text-3xl md:text-5xl lg:text-6xl font-bold text-base-content">
                            {{ !empty($streamState['question']) ? 'Aktuelle Frage' : 'Warte auf die nächste Frage' }}
                        </h1>

                        <p id="stream-subline" class="text-base-content/70 text-lg md:text-xl">
                            {{ $streamState['status_label'] ?? '—' }}
                        </p>

                        <div id="stream-question-meta" class="text-sm md:text-base text-base-content/60">
                            @if(!empty($streamState['question']))
                                Frage {{ ($streamState['current_index'] ?? 0) + 1 }} von {{ $streamState['total_questions'] ?? 0 }}
                                • {{ $streamState['question']['points'] ?? 0 }} Punkte
                                @if(!empty($streamState['question']['catalog']))
                                    • {{ $streamState['question']['catalog'] }}
                                @endif
                            @endif
                        </div>
                    </div>

                    <div id="stream-question-wrap" class="{{ !empty($streamState['question']) ? '' : 'hidden-soft' }}">
                        <div class="space-y-6">
                            <h2 id="stream-question-text" class="text-2xl md:text-4xl lg:text-5xl font-bold leading-tight text-base-content">
                                {{ $streamState['question']['text'] ?? '—' }}
                            </h2>

                            <div id="stream-question-image-wrap" class="{{ !empty($streamState['question']['image_url']) ? '' : 'hidden-soft' }}">
                                <div class="rounded-3xl overflow-hidden border border-white/10 bg-base-200">
                                    <img
                                        id="stream-question-image"
                                        src="{{ $streamState['question']['image_url'] ?? '' }}"
                                        alt="Fragenbild"
                                        class="w-full max-h-[420px] object-contain"
                                    >
                                </div>
                            </div>

                            <div id="stream-answers-wrap"></div>

                            <div id="stream-reveal-wrap" class="{{ !empty($streamState['revealed']) ? '' : 'hidden-soft' }}">
                                <div id="stream-reveal-content"></div>
                            </div>
                        </div>
                    </div>

                    <div id="stream-empty-state" class="{{ empty($streamState['question']) ? '' : 'hidden-soft' }}">
                        <div class="text-xl md:text-2xl text-base-content/70">
                            Der Stream wartet gerade auf die nächste Aktion.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="players-area">
            <div class="glass-panel rounded-3xl px-6 py-6 md:px-8 md:py-8">
                <div class="flex items-center justify-between gap-4 mb-5">
                    <h3 class="text-2xl md:text-3xl font-bold text-base-content">
                        Teilnehmer & Punktestand
                    </h3>

                    <div id="stream-player-count" class="badge badge-outline badge-lg">
                        0 Spieler
                    </div>
                </div>

                <div id="stream-leaderboard-list" class="player-grid"></div>
            </div>
        </div>
    </div>

    <script>
        const questionTypes = window.quizQuestionTypes || {};
        let pollHandle = null;
        let currentState = window.quizStreamState || {};

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizeArray(value) {
            return Array.isArray(value) ? value : [];
        }

        function setText(id, value, fallback = '—') {
            const el = document.getElementById(id);
            if (!el) return;
            el.textContent = value ?? fallback;
        }

        function toggle(id, visible) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('hidden-soft', !visible);
        }

        function renderQuestionMeta(state) {
            const question = state.question || null;
            const currentIndex = typeof state.current_index === 'number' ? state.current_index : null;
            const totalQuestions = state.total_questions ?? 0;

            if (!question) {
                setText('stream-question-meta', '');
                return;
            }

            let parts = [];

            if (currentIndex !== null) {
                parts.push(`Frage ${currentIndex + 1} von ${totalQuestions}`);
            }

            if (question.points !== undefined && question.points !== null) {
                parts.push(`${question.points} Punkte`);
            }

            if (question.catalog) {
                parts.push(question.catalog);
            }

            setText('stream-question-meta', parts.join(' • '), '');
        }

        function getCorrectAnswers(question) {
            if (!question) return [];

            if (Array.isArray(question.correct_answers)) {
                return question.correct_answers;
            }

            if (question.correct_answer_text) {
                return [question.correct_answer_text];
            }

            if (question.answer_text) {
                return [question.answer_text];
            }

            if (question.correct_option_text) {
                return [question.correct_option_text];
            }

            return [];
        }

        function renderAnswers(state) {
            const wrap = document.getElementById('stream-answers-wrap');
            if (!wrap) return;

            const question = state.question || null;
            const revealed = !!state.revealed;

            if (!question) {
                wrap.innerHTML = '';
                return;
            }

            const options = normalizeArray(question.options);
            const correctAnswers = getCorrectAnswers(question).map(String);
            const type = question.type || '';

            if (options.length > 0) {
                const html = `
                    <div class="answer-grid mt-2">
                        ${options.map((option, index) => {
                            const optionText = typeof option === 'object'
                                ? (option.text ?? option.label ?? option.answer ?? '')
                                : option;

                            const isCorrect = correctAnswers.includes(String(optionText));

                            let classes = 'answer-card rounded-2xl border border-white/10 bg-base-200/70 px-5 py-4 text-left';

                            if (revealed) {
                                classes += isCorrect ? ' correct' : ' wrong';
                            }

                            return `
                                <div class="${classes}">
                                    <div class="text-sm text-base-content/50 mb-1">
                                        Antwort ${index + 1}
                                    </div>
                                    <div class="text-lg md:text-2xl font-semibold text-base-content">
                                        ${escapeHtml(optionText)}
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;

                wrap.innerHTML = html;
                return;
            }

            if (type === 'estimate' || type === 'schätzfrage') {
                wrap.innerHTML = revealed && correctAnswers.length
                    ? `
                        <div class="mt-4 rounded-2xl border border-success/30 bg-success/10 px-6 py-5">
                            <div class="text-sm uppercase tracking-wide text-success mb-1">Richtige Lösung</div>
                            <div class="text-2xl md:text-4xl font-bold">${escapeHtml(correctAnswers[0])}</div>
                        </div>
                    `
                    : '';
                return;
            }

            if (revealed && correctAnswers.length) {
                wrap.innerHTML = `
                    <div class="mt-4 rounded-2xl border border-success/30 bg-success/10 px-6 py-5">
                        <div class="text-sm uppercase tracking-wide text-success mb-1">Richtige Lösung</div>
                        <div class="text-xl md:text-3xl font-bold">
                            ${correctAnswers.map(answer => escapeHtml(answer)).join('<br>')}
                        </div>
                    </div>
                `;
                return;
            }

            wrap.innerHTML = '';
        }

        function renderReveal(state) {
            const wrap = document.getElementById('stream-reveal-wrap');
            const content = document.getElementById('stream-reveal-content');

            if (!wrap || !content) return;

            const question = state.question || null;
            const revealed = !!state.revealed;

            if (!question || !revealed) {
                wrap.classList.add('hidden-soft');
                content.innerHTML = '';
                return;
            }

            const correctAnswers = getCorrectAnswers(question);
            const explanation = question.explanation || question.answer_explanation || null;

            content.innerHTML = `
                <div class="rounded-2xl border border-success/30 bg-success/10 px-6 py-5 reveal-highlight">
                    <div class="text-sm uppercase tracking-wide text-success mb-2">Auflösung</div>
                    <div class="text-xl md:text-3xl font-bold text-base-content">
                        ${correctAnswers.length ? correctAnswers.map(answer => escapeHtml(answer)).join('<br>') : 'Lösung eingeblendet'}
                    </div>
                    ${explanation ? `
                        <div class="mt-3 text-base md:text-lg text-base-content/80">
                            ${escapeHtml(explanation)}
                        </div>
                    ` : ''}
                </div>
            `;

            wrap.classList.remove('hidden-soft');
        }

        function renderPlayers(state) {
            const leaderboard = normalizeArray(state.leaderboard);
            const list = document.getElementById('stream-leaderboard-list');
            const count = document.getElementById('stream-player-count');

            if (!list || !count) return;

            count.textContent = `${leaderboard.length} Spieler`;

            if (!leaderboard.length) {
                list.innerHTML = `
                    <div class="col-span-full rounded-2xl border border-white/10 bg-base-200/60 px-5 py-5 text-base-content/70">
                        Noch keine Teilnehmer sichtbar.
                    </div>
                `;
                return;
            }

            list.innerHTML = leaderboard.map((player, index) => {
                const name = player.name ?? player.username ?? player.display_name ?? `Spieler ${index + 1}`;
                const score = player.score ?? player.points ?? 0;
                const rank = player.rank ?? player.position ?? (index + 1);
                const isTop = index === 0;

                return `
                    <div class="player-card rounded-2xl border border-white/10 bg-base-200/70 px-5 py-4 ${isTop ? 'top-player' : ''}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm text-base-content/50 mb-1">
                                    Platz ${escapeHtml(rank)}
                                </div>
                                <div class="text-lg md:text-2xl font-bold text-base-content truncate">
                                    ${escapeHtml(name)}
                                </div>
                            </div>

                            <div class="badge ${isTop ? 'badge-warning' : 'badge-outline'} badge-lg">
                                ${escapeHtml(score)} P
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderState(state) {
            currentState = state || {};

            const question = currentState.question || null;
            const revealed = !!currentState.revealed;
            const paused = !!currentState.paused;

            setText('stream-status-badge', currentState.status_label ?? '—');
            setText(
                'stream-progress-badge',
                `Frage ${typeof currentState.current_index === 'number' ? currentState.current_index + 1 : 0}/${currentState.total_questions ?? 0}`
            );
            setText('stream-phase-badge', revealed ? 'Auflösung' : (paused ? 'Pausiert' : 'Frage läuft'));

            setText('stream-headline', question ? 'Aktuelle Frage' : 'Warte auf die nächste Frage');
            setText('stream-subline', currentState.status_label ?? '—');
            setText(
                'stream-question-type',
                question?.type ? (questionTypes[question.type] ?? question.type) : ''
            );

            renderQuestionMeta(currentState);

            toggle('stream-question-wrap', !!question);
            toggle('stream-empty-state', !question);

            if (question) {
                setText('stream-question-text', question.text ?? '—');

                const imageWrap = document.getElementById('stream-question-image-wrap');
                const image = document.getElementById('stream-question-image');

                if (question.image_url) {
                    image.src = question.image_url;
                    imageWrap.classList.remove('hidden-soft');
                } else {
                    image.src = '';
                    imageWrap.classList.add('hidden-soft');
                }
            }

            renderAnswers(currentState);
            renderReveal(currentState);
            renderPlayers(currentState);
        }

        async function updateStream() {
            try {
                const response = await fetch(window.quizStreamStateUrl, {
                    method: 'GET',
                    cache: 'no-store',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                renderState(data);
            } catch (error) {
                console.error('Fehler beim Aktualisieren des Stream-States:', error);
            }
        }

        renderState(currentState);
        pollHandle = setInterval(updateStream, 1000);
    </script>
</body>
</html>