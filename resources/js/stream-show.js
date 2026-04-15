function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function toggle(el, show) {
    if (!el) return;
    el.classList.toggle('hidden', !show);
}

function statusBadgeClass(status, paused = false) {
    if (paused) return 'badge badge-warning';

    switch (status) {
        case 'scheduled':
            return 'badge badge-warning';
        case 'live':
            return 'badge badge-success';
        case 'ended':
            return 'badge badge-error';
        default:
            return 'badge badge-ghost';
    }
}

function buildFingerprint(payload) {
    return JSON.stringify({
        status: payload.status,
        paused: !!payload.paused,
        revealed: !!payload.revealed,
        current_index: payload.current_index,
        total_questions: payload.total_questions,
        question_id: payload.question?.id ?? null,
        leaderboard: payload.leaderboard ?? [],
    });
}

function createTransport({ quizId, stateUrl, onState }) {
    let pollInterval = null;
    let subscribed = false;

    const applyState = async () => {
        if (!stateUrl) return;

        try {
            const response = await fetch(stateUrl, {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) return;

            const payload = await response.json();
            onState(payload);
        } catch {
            // ignore
        }
    };

    const startPolling = () => {
        if (!stateUrl || pollInterval) return;
        applyState();
        pollInterval = window.setInterval(applyState, 3000);
    };

    const stopPolling = () => {
        if (!pollInterval) return;
        window.clearInterval(pollInterval);
        pollInterval = null;
    };

    const startEcho = () => {
        if (subscribed || !window.Echo || !quizId) return false;

        subscribed = true;

        window.Echo.private(`quiz.${quizId}`)
            .listen('.quiz.state.updated', (payload) => {
                onState(payload);
            });

        stopPolling();
        return true;
    };

    const start = () => {
        if (!startEcho()) {
            startPolling();

            let tries = 0;
            const waiter = window.setInterval(() => {
                tries += 1;

                if (startEcho()) {
                    window.clearInterval(waiter);
                    return;
                }

                if (tries >= 30) {
                    window.clearInterval(waiter);
                }
            }, 500);
        }
    };

    return { start };
}

export function initQuizStream() {
    const root = document.getElementById('quiz-stream-root');
    if (!root) return;

    const state = window.quizStreamState || {};
    const statusLabels = window.quizStatusLabels || {};
    const questionTypes = window.quizQuestionTypes || {};
    const stateUrl = window.quizStreamStateUrl || null;

    const els = {
        statusBadge: document.getElementById('stream-status-badge'),
        progressBadge: document.getElementById('stream-progress-badge'),
        headline: document.getElementById('stream-headline'),
        subline: document.getElementById('stream-subline'),
        questionWrap: document.getElementById('stream-question-wrap'),
        questionType: document.getElementById('stream-question-type'),
        questionText: document.getElementById('stream-question-text'),
        questionMeta: document.getElementById('stream-question-meta'),
        questionImageWrap: document.getElementById('stream-question-image-wrap'),
        questionImage: document.getElementById('stream-question-image'),
        answersWrap: document.getElementById('stream-answers-wrap'),
        leaderboardWrap: document.getElementById('stream-leaderboard-wrap'),
        leaderboardList: document.getElementById('stream-leaderboard-list'),
        revealWrap: document.getElementById('stream-reveal-wrap'),
        revealContent: document.getElementById('stream-reveal-content'),
    };

    let lastFingerprint = null;

    function renderAnswers(question) {
        const options = Array.isArray(question?.options) ? question.options : [];
        if (!els.answersWrap) return;

        if (!options.length) {
            els.answersWrap.innerHTML = '';
            return;
        }

        els.answersWrap.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                ${options.map(option => `
                    <div class="rounded-2xl border border-base-300 bg-base-100/80 p-5">
                        ${option.label ? `<div class="badge badge-primary mb-3">${escapeHtml(option.label)}</div>` : ''}
                        ${option.text ? `<div class="text-xl font-semibold">${escapeHtml(option.text)}</div>` : ''}
                        ${option.image_url ? `
                            <div class="mt-4 rounded-xl overflow-hidden border border-base-300 bg-base-200">
                                <img src="${escapeHtml(option.image_url)}" alt="Antwortbild" class="w-full max-h-64 object-contain">
                            </div>
                        ` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    function renderLeaderboard(payload) {
        const leaderboard = Array.isArray(payload.leaderboard) ? payload.leaderboard.slice(0, 8) : [];

        if (!els.leaderboardList) return;

        if (!leaderboard.length) {
            els.leaderboardList.innerHTML = `<div class="text-base-content/60">Noch keine Punktestände verfügbar.</div>`;
            return;
        }

        els.leaderboardList.innerHTML = leaderboard.map(entry => `
            <div class="flex items-center justify-between rounded-xl border border-base-300 bg-base-100/70 px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="badge badge-primary">${entry.rank}</span>
                    <span class="font-semibold">${escapeHtml(entry.name)}</span>
                </div>
                <span class="badge badge-secondary">${entry.score} Pkt</span>
            </div>
        `).join('');
    }

    function renderCorrectAnswer(payload) {
        const question = payload.question;
        if (!question) return '';

        const type = question.type;
        const options = question.options || [];

        if (['single_choice', 'true_false', 'image_choice'].includes(type)) {
            const correct = options.find(o => !!o.is_correct);
            return correct
                ? `<div><strong>${escapeHtml(correct.label || '')}</strong>${correct.text ? `: ${escapeHtml(correct.text)}` : ''}</div>`
                : '<div>Keine richtige Antwort hinterlegt.</div>';
        }

        if (type === 'multiple_choice') {
            const correct = options.filter(o => !!o.is_correct);
            return correct.length
                ? `<div class="space-y-2">${correct.map(o => `<div><strong>${escapeHtml(o.label || '')}</strong>${o.text ? `: ${escapeHtml(o.text)}` : ''}</div>`).join('')}</div>`
                : '<div>Keine richtige Antwort hinterlegt.</div>';
        }

        if (type === 'sorting') {
            const ordered = [...options].sort((a, b) => Number(a.sort_order || 0) - Number(b.sort_order || 0));
            return `
                <div class="space-y-2">
                    ${ordered.map((o, i) => `
                        <div>${i + 1}. <strong>${escapeHtml(o.label || '')}</strong>${o.text ? `: ${escapeHtml(o.text)}` : ''}</div>
                    `).join('')}
                </div>
            `;
        }

        if (type === 'text') return '<div>Textlösung serverseitig hinterlegt.</div>';
        if (['date', 'date_guess'].includes(type)) return `<div>${escapeHtml(question.correct_date_answer || 'Keine richtige Antwort hinterlegt.')}</div>`;
        if (['number', 'number_guess', 'numeric_guess', 'estimate'].includes(type)) return `<div>${escapeHtml(question.correct_numeric_answer ?? 'Keine richtige Antwort hinterlegt.')}</div>`;

        return '<div>Keine richtige Antwort hinterlegt.</div>';
    }

    function applyState(payload) {
        const merged = {
            ...state,
            ...payload,
            status_label: payload.status_label || statusLabels[payload.status] || payload.status,
        };

        const fingerprint = buildFingerprint(merged);
        if (fingerprint === lastFingerprint) return;
        lastFingerprint = fingerprint;

        Object.assign(state, merged);

        const currentNumber = state.question && state.current_index !== null
            ? state.current_index + 1
            : 0;

        if (els.statusBadge) {
            els.statusBadge.className = statusBadgeClass(state.status, !!state.paused);
            els.statusBadge.textContent = state.status_label;
        }

        if (els.progressBadge) {
            els.progressBadge.textContent = `Frage ${currentNumber}/${state.total_questions}`;
        }

        if (els.headline) {
            if (state.paused) {
                els.headline.textContent = 'Quiz pausiert';
            } else if (state.status === 'ended') {
                els.headline.textContent = 'Quiz beendet';
            } else if (!state.question) {
                els.headline.textContent = 'Warte auf die nächste Frage';
            } else if (state.revealed) {
                els.headline.textContent = 'Auflösung';
            } else {
                els.headline.textContent = 'Aktuelle Frage';
            }
        }

        if (els.subline) {
            els.subline.textContent = state.status_label || '';
        }

        toggle(els.questionWrap, !!state.question && !state.paused);
        toggle(els.leaderboardWrap, !!state.paused || state.status === 'ended');
        toggle(els.revealWrap, !!state.question && !!state.revealed && !state.paused);

        if (els.leaderboardWrap) {
            renderLeaderboard(state);
        }

        if (!state.question) {
            if (els.answersWrap) els.answersWrap.innerHTML = '';
            if (els.revealContent) els.revealContent.innerHTML = '';
            return;
        }

        if (els.questionType) {
            els.questionType.textContent = questionTypes[state.question.type] || state.question.type || '—';
        }

        if (els.questionText) {
            els.questionText.textContent = state.question.text || '';
        }

        if (els.questionMeta) {
            const parts = [
                `Frage ${currentNumber} von ${state.total_questions}`,
                `${state.question.points ?? 0} Punkte`,
            ];

            if (state.question.catalog) {
                parts.push(state.question.catalog);
            }

            els.questionMeta.textContent = parts.join(' • ');
        }

        if (els.questionImage && els.questionImageWrap) {
            const nextImage = state.question.image_url || '';
            if (els.questionImage.getAttribute('src') !== nextImage) {
                els.questionImage.setAttribute('src', nextImage);
            }
            toggle(els.questionImageWrap, !!nextImage);
        }

        renderAnswers(state.question);

        if (state.revealed && els.revealContent) {
            els.revealContent.innerHTML = `
                <div class="card bg-base-100/80 border border-base-300">
                    <div class="card-body">
                        <h3 class="card-title">Richtige Antwort</h3>
                        ${renderCorrectAnswer(state)}
                        ${state.question.explanation ? `<div class="mt-4 text-base-content/70">${escapeHtml(state.question.explanation)}</div>` : ''}
                    </div>
                </div>
            `;
        } else if (els.revealContent) {
            els.revealContent.innerHTML = '';
        }
    }

    applyState(state);

    createTransport({
        quizId: state.quiz_id,
        stateUrl,
        onState: applyState,
    }).start();
}