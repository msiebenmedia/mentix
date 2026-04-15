function escapeHtml(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function isEqual(a, b) {
    return JSON.stringify(a) === JSON.stringify(b);
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
        has_answered: !!payload.has_answered,
        player_answer: payload.player_answer ?? null,
        player_score: payload.player_score ?? null,
        leaderboard: payload.leaderboard ?? [],
    });
}

function renderChoiceOptions(question, inputType = 'radio', inputName = 'question_option_id') {
    const options = Array.isArray(question?.options) ? question.options : [];

    return `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            ${options.map(option => `
                <label class="border border-base-300 rounded-xl p-4 flex items-start gap-3 cursor-pointer hover:bg-base-200 transition">
                    <input
                        type="${inputType}"
                        name="${inputName}"
                        value="${option.id}"
                        class="${inputType === 'checkbox' ? 'checkbox checkbox-primary' : 'radio radio-primary'} mt-1 shrink-0"
                    >

                    <div class="flex-1">
                        ${option.label ? `<div class="font-semibold">${escapeHtml(option.label)}</div>` : ''}
                        ${option.text ? `<div class="text-base-content/80">${escapeHtml(option.text)}</div>` : ''}
                        ${option.image_url ? `
                            <div class="mt-3 rounded-xl overflow-hidden border border-base-300 bg-base-200">
                                <img src="${escapeHtml(option.image_url)}" alt="Antwortbild" class="w-full max-h-56 object-contain">
                            </div>
                        ` : ''}
                    </div>
                </label>
            `).join('')}
        </div>
    `;
}

function buildSortingList(options) {
    const shuffled = [...(options || [])];

    for (let i = shuffled.length - 1; i > 0; i -= 1) {
        const j = Math.floor(Math.random() * (i + 1));
        [shuffled[i], shuffled[j]] = [shuffled[j], shuffled[i]];
    }

    return `
        <div class="alert alert-info">
            <span>Bringe die Antworten in die richtige Reihenfolge.</span>
        </div>

        <div class="space-y-3" id="sorting-list">
            ${shuffled.map((option, index) => `
                <div class="border border-base-300 rounded-xl p-4 flex items-center gap-4 sorting-item" data-option-id="${option.id}">
                    <div class="badge badge-outline min-w-10">${index + 1}</div>

                    <div class="flex-1">
                        ${option.label ? `<div class="font-semibold">${escapeHtml(option.label)}</div>` : ''}
                        ${option.text ? `<div class="text-base-content/80">${escapeHtml(option.text)}</div>` : ''}
                        ${option.image_url ? `
                            <div class="mt-3 rounded-xl overflow-hidden border border-base-300 bg-base-200">
                                <img src="${escapeHtml(option.image_url)}" alt="Sortierbild" class="w-full max-h-56 object-contain">
                            </div>
                        ` : ''}
                    </div>

                    <div class="flex flex-col gap-2">
                        <button type="button" class="btn btn-sm sorting-up">
                            <i class="ti ti-arrow-up"></i>
                        </button>
                        <button type="button" class="btn btn-sm sorting-down">
                            <i class="ti ti-arrow-down"></i>
                        </button>
                    </div>
                </div>
            `).join('')}
        </div>

        <div id="sorting-hidden-inputs"></div>
    `;
}

function syncSortingHiddenInputs(form) {
    const list = form.querySelector('#sorting-list');
    const hiddenContainer = form.querySelector('#sorting-hidden-inputs');

    if (!list || !hiddenContainer) return;

    const items = [...list.querySelectorAll('.sorting-item')];

    hiddenContainer.innerHTML = items.map((item) => {
        return `<input type="hidden" name="question_option_ids[]" value="${item.dataset.optionId}">`;
    }).join('');

    items.forEach((item, index) => {
        const badge = item.querySelector('.badge');
        if (badge) badge.textContent = index + 1;
    });
}

function bindSortingControls(form) {
    const list = form.querySelector('#sorting-list');
    if (!list) return;

    list.addEventListener('click', (event) => {
        const upButton = event.target.closest('.sorting-up');
        const downButton = event.target.closest('.sorting-down');
        if (!upButton && !downButton) return;

        const item = event.target.closest('.sorting-item');
        if (!item) return;

        if (upButton) {
            const previous = item.previousElementSibling;
            if (previous) {
                list.insertBefore(item, previous);
            }
        }

        if (downButton) {
            const next = item.nextElementSibling;
            if (next) {
                list.insertBefore(next, item);
            }
        }

        syncSortingHiddenInputs(form);
    });

    syncSortingHiddenInputs(form);
}

function renderPlayerAnswerText(payload) {
    if (!payload.player_answer || !payload.question) return 'Keine Antwort';

    const type = payload.question.type;
    const options = payload.question.options || [];

    if (['single_choice', 'true_false', 'image_choice'].includes(type)) {
        const selected = options.find(o => Number(o.id) === Number(payload.player_answer.question_option_id));
        if (!selected) return 'Keine Antwort';
        return selected.label && selected.text
            ? `${selected.label}: ${selected.text}`
            : (selected.text || selected.label || 'Keine Antwort');
    }

    if (type === 'multiple_choice') {
        const selectedIds = Array.isArray(payload.player_answer.answer_json)
            ? payload.player_answer.answer_json.map(id => Number(id))
            : [];

        const selected = options.filter(o => selectedIds.includes(Number(o.id)));

        return selected.map(option => {
            return option.label && option.text
                ? `${option.label}: ${option.text}`
                : (option.text || option.label || '');
        }).join(', ');
    }

    if (type === 'sorting') {
        const order = payload.player_answer.answer_json?.order || [];

        return order.map((id, index) => {
            const option = options.find(o => Number(o.id) === Number(id));
            if (!option) return `${index + 1}. —`;

            const text = option.label && option.text
                ? `${option.label}: ${option.text}`
                : (option.text || option.label || '—');

            return `${index + 1}. ${text}`;
        }).join(' | ');
    }

    if (type === 'text') return payload.player_answer.answer_text || 'Keine Antwort';
    if (['date', 'date_guess'].includes(type)) return payload.player_answer.answer_date || 'Keine Antwort';
    if (['number', 'number_guess', 'numeric_guess', 'estimate'].includes(type)) {
        return payload.player_answer.answer_numeric ?? 'Keine Antwort';
    }

    return 'Keine Antwort';
}

function renderCorrectAnswer(payload) {
    if (!payload.question) return '';

    const type = payload.question.type;
    const options = payload.question.options || [];

    if (['single_choice', 'true_false', 'image_choice'].includes(type)) {
        const correct = options.find(o => !!o.is_correct);

        if (!correct) {
            return `<div>Keine richtige Antwort hinterlegt.</div>`;
        }

        return `
            <div>
                <strong>${escapeHtml(correct.label || '')}</strong>
                ${correct.text ? `: ${escapeHtml(correct.text)}` : ''}
            </div>
        `;
    }

    if (type === 'multiple_choice') {
        const correct = options.filter(o => !!o.is_correct);

        if (correct.length === 0) {
            return `<div>Keine richtige Antwort hinterlegt.</div>`;
        }

        return `
            <div class="space-y-2">
                ${correct.map(option => `
                    <div>
                        <strong>${escapeHtml(option.label || '')}</strong>
                        ${option.text ? `: ${escapeHtml(option.text)}` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    if (type === 'sorting') {
        const ordered = [...options].sort((a, b) => Number(a.sort_order || 0) - Number(b.sort_order || 0));

        return `
            <div class="space-y-2">
                ${ordered.map((option, index) => `
                    <div>
                        ${index + 1}. <strong>${escapeHtml(option.label || '')}</strong>
                        ${option.text ? `: ${escapeHtml(option.text)}` : ''}
                    </div>
                `).join('')}
            </div>
        `;
    }

    if (type === 'text') {
        return `<div>Textlösung wird aktuell nur serverseitig angezeigt, falls hinterlegt.</div>`;
    }

    if (['date', 'date_guess'].includes(type)) {
        return `<div>${escapeHtml(payload.question.correct_date_answer || 'Keine richtige Antwort hinterlegt.')}</div>`;
    }

    if (['number', 'number_guess', 'numeric_guess', 'estimate'].includes(type)) {
        return `<div>${escapeHtml(payload.question.correct_numeric_answer ?? 'Keine richtige Antwort hinterlegt.')}</div>`;
    }

    return `<div>Keine richtige Antwort hinterlegt.</div>`;
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
            // still
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

export function initQuizPlayer() {
    const root = document.getElementById('quiz-player-root');
    if (!root) return;

    const state = window.quizPlayerState || {};
    const statusLabels = window.quizStatusLabels || {};
    const questionTypes = window.quizQuestionTypes || {};
    const answerSubmitUrl = window.quizAnswerSubmitUrl || '';
    const csrfToken = window.quizCsrfToken || '';
    const stateUrl = window.quizPlayerStateUrl || null;

    const els = {
        statusBadge: document.getElementById('quiz-status-badge'),
        progressBadge: document.getElementById('quiz-progress-badge'),
        statusText: document.getElementById('quiz-status-text'),
        statusAlerts: document.getElementById('quiz-status-alerts'),
        playerScoreValue: document.getElementById('player-score-value'),
        pauseLeaderboardCard: document.getElementById('pause-leaderboard-card'),
        pauseLeaderboardList: document.getElementById('pause-leaderboard-list'),
        questionCard: document.getElementById('question-card'),
        questionEmpty: document.getElementById('question-empty'),
        questionContent: document.getElementById('question-content'),
        questionTypeBadge: document.getElementById('question-type-badge'),
        questionPosition: document.getElementById('question-position'),
        questionText: document.getElementById('question-text'),
        questionPoints: document.getElementById('question-points'),
        questionCatalog: document.getElementById('question-catalog'),
        questionImageWrapper: document.getElementById('question-image-wrapper'),
        questionImage: document.getElementById('question-image'),
        answerFormContainer: document.getElementById('answer-form-container'),
    };

    let lastFingerprint = null;
    let formStateKey = null;

    function renderAlerts(payload) {
        if (!els.statusAlerts) return;

        let html = '';

        if (payload.status === 'scheduled') {
            html += `
                <div class="alert alert-warning">
                    <span>Dieses Quiz ist geplant und noch nicht gestartet.</span>
                </div>
            `;
        }

        if (payload.paused) {
            html += `
                <div class="alert alert-warning">
                    <span>Das Quiz ist aktuell pausiert. Hier siehst du die aktuellen Punktestände.</span>
                </div>
            `;
        } else if (payload.status === 'draft') {
            html += `
                <div class="alert alert-info">
                    <span>Das Quiz ist aktuell noch nicht live.</span>
                </div>
            `;
        }

        if (payload.status === 'ended') {
            html += `
                <div class="alert alert-success">
                    <span>Dieses Quiz wurde bereits beendet.</span>
                </div>
            `;
        }

        els.statusAlerts.innerHTML = html;
    }

    function renderLeaderboard(payload) {
        const leaderboard = Array.isArray(payload.leaderboard) ? payload.leaderboard : [];

        if (!els.pauseLeaderboardList) return;

        if (leaderboard.length === 0) {
            els.pauseLeaderboardList.innerHTML = `<div class="text-base-content/60">Keine Punktestände verfügbar.</div>`;
            return;
        }

        els.pauseLeaderboardList.innerHTML = leaderboard.map((entry) => `
            <div class="flex items-center justify-between border border-base-300 rounded-xl px-4 py-3">
                <div class="flex items-center gap-3">
                    <span class="badge badge-primary">${entry.rank}</span>
                    <span class="font-medium">${escapeHtml(entry.name)}</span>
                </div>
                <span class="badge badge-secondary">${entry.score} Pkt</span>
            </div>
        `).join('');
    }

    function getAnswerFormStateKey(payload) {
        return JSON.stringify({
            q: payload.question?.id ?? null,
            revealed: !!payload.revealed,
            paused: !!payload.paused,
            status: payload.status,
            has_answered: !!payload.has_answered,
            player_answer: payload.player_answer ?? null,
        });
    }

    function renderAnswerForm(payload) {
        if (!els.answerFormContainer) return;

        const nextFormStateKey = getAnswerFormStateKey(payload);

        if (formStateKey === nextFormStateKey) {
            return;
        }

        formStateKey = nextFormStateKey;

        if (payload.paused) {
            els.answerFormContainer.innerHTML = `
                <div class="alert alert-warning">
                    <span>Das Quiz ist pausiert.</span>
                </div>
            `;
            return;
        }

        if (!payload.question) {
            els.answerFormContainer.innerHTML = `
                <div class="alert alert-info">
                    <span>Aktuell ist keine Frage aktiv.</span>
                </div>
            `;
            return;
        }

        if (payload.revealed) {
            if (payload.player_answer) {
                const answerText = renderPlayerAnswerText(payload);
                const resultClass = payload.player_answer.is_correct ? 'alert-success' : 'alert-error';
                const resultText = payload.player_answer.is_correct ? 'richtig' : 'falsch';

                els.answerFormContainer.innerHTML = `
                    <div class="space-y-4">
                        <div class="alert ${resultClass}">
                            <span>Deine Antwort <strong>${escapeHtml(answerText)}</strong> war ${resultText}.</span>
                        </div>

                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3">Richtige Antwort</h3>
                                ${renderCorrectAnswer(payload)}
                                ${payload.question.explanation ? `<div class="mt-4 text-sm text-base-content/70">${escapeHtml(payload.question.explanation)}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            } else {
                els.answerFormContainer.innerHTML = `
                    <div class="space-y-4">
                        <div class="alert alert-warning">
                            <span>Du hast für diese Frage keine Antwort abgegeben.</span>
                        </div>

                        <div class="card bg-base-200 border border-base-300">
                            <div class="card-body">
                                <h3 class="font-semibold mb-3">Richtige Antwort</h3>
                                ${renderCorrectAnswer(payload)}
                                ${payload.question.explanation ? `<div class="mt-4 text-sm text-base-content/70">${escapeHtml(payload.question.explanation)}</div>` : ''}
                            </div>
                        </div>
                    </div>
                `;
            }

            return;
        }

        if (payload.player_answer || payload.has_answered) {
            els.answerFormContainer.innerHTML = `
                <div class="alert alert-success">
                    <span>Deine Antwort wurde bereits gespeichert.</span>
                </div>
            `;
            return;
        }

        if (payload.status !== 'live') {
            els.answerFormContainer.innerHTML = `
                <div class="alert alert-info">
                    <span>Antworten sind erst möglich, wenn das Quiz live ist.</span>
                </div>
            `;
            return;
        }

        const type = payload.question.type;
        let innerHtml = '';

        if (['single_choice', 'true_false', 'image_choice'].includes(type)) {
            innerHtml = renderChoiceOptions(payload.question, 'radio', 'question_option_id');
        } else if (type === 'multiple_choice') {
            innerHtml = renderChoiceOptions(payload.question, 'checkbox', 'question_option_ids[]');
        } else if (type === 'sorting') {
            innerHtml = buildSortingList(payload.question.options || []);
        } else if (type === 'text') {
            innerHtml = `
                <div class="form-control">
                    <label class="label">
                        <span class="label-text">Antwort eingeben</span>
                    </label>
                    <textarea
                        name="answer_text"
                        class="textarea textarea-bordered w-full min-h-32"
                        placeholder="Deine Antwort..."
                        required
                    ></textarea>
                </div>
            `;
        } else if (['date', 'date_guess'].includes(type)) {
            innerHtml = `
                <div class="form-control max-w-sm">
                    <label class="label">
                        <span class="label-text">Datum eingeben</span>
                    </label>
                    <input
                        type="date"
                        name="answer_date"
                        class="input input-bordered w-full"
                        required
                    >
                </div>
            `;
        } else if (['number', 'number_guess', 'numeric_guess', 'estimate'].includes(type)) {
            innerHtml = `
                <div class="form-control max-w-sm">
                    <label class="label">
                        <span class="label-text">Zahl eingeben</span>
                    </label>
                    <input
                        type="number"
                        step="0.01"
                        name="answer_numeric"
                        class="input input-bordered w-full"
                        required
                    >
                </div>
            `;
        } else {
            els.answerFormContainer.innerHTML = `
                <div class="alert alert-warning">
                    <span>Dieser Antworttyp wird noch nicht unterstützt.</span>
                </div>
            `;
            return;
        }

        els.answerFormContainer.innerHTML = `
            <form method="POST" action="${escapeHtml(answerSubmitUrl)}" class="space-y-4" id="quiz-answer-form">
                <input type="hidden" name="_token" value="${escapeHtml(csrfToken)}">
                ${innerHtml}
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-send"></i>
                        Antwort absenden
                    </button>
                </div>
            </form>
        `;

        bindAnswerFormAjax();
    }

    function bindAnswerFormAjax() {
        const form = document.getElementById('quiz-answer-form');
        if (!form) return;

        bindSortingControls(form);

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            if (form.querySelector('#sorting-list')) {
                syncSortingHiddenInputs(form);
            }

            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) submitButton.disabled = true;

            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const data = await response.json();

                if (!response.ok || !data.success) {
                    els.answerFormContainer.innerHTML = `
                        <div class="alert alert-error">
                            <span>${escapeHtml(data.message || 'Die Antwort konnte nicht gespeichert werden.')}</span>
                        </div>
                    `;
                    formStateKey = null;
                    return;
                }

                state.has_answered = true;
                state.player_answer = data.player_answer || null;
                state.player_score = data.player_score ?? state.player_score;

                if (els.playerScoreValue && typeof data.player_score !== 'undefined') {
                    els.playerScoreValue.textContent = data.player_score;
                }

                renderAnswerForm(state);
            } catch {
                els.answerFormContainer.innerHTML = `
                    <div class="alert alert-error">
                        <span>Beim Speichern der Antwort ist ein Fehler aufgetreten.</span>
                    </div>
                `;
                formStateKey = null;
            } finally {
                if (submitButton) submitButton.disabled = false;
            }
        }, { once: true });
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

        const previousQuestionId = state.question?.id ?? null;
        const nextQuestionId = merged.question?.id ?? null;
        const questionChanged = previousQuestionId !== nextQuestionId;

        Object.assign(state, merged);

        if (questionChanged) {
            state.has_answered = false;
            state.player_answer = null;
            formStateKey = null;
        }

        if (els.statusBadge) {
            els.statusBadge.className = statusBadgeClass(state.status, !!state.paused);
            els.statusBadge.textContent = state.status_label;
        }

        if (els.statusText) {
            els.statusText.textContent = state.status_label;
        }

        if (els.playerScoreValue && typeof state.player_score !== 'undefined') {
            els.playerScoreValue.textContent = state.player_score;
        }

        const currentNumber = state.question && state.current_index !== null
            ? state.current_index + 1
            : 0;

        if (els.progressBadge) {
            els.progressBadge.textContent = `Frage ${currentNumber}/${state.total_questions}`;
        }

        renderAlerts(state);
        renderLeaderboard(state);

        toggle(els.pauseLeaderboardCard, !!state.paused);
        toggle(els.questionCard, !state.paused);

        if (!state.question) {
            toggle(els.questionEmpty, true);
            toggle(els.questionContent, false);
            renderAnswerForm(state);
            return;
        }

        toggle(els.questionEmpty, false);
        toggle(els.questionContent, true);

        if (els.questionPosition) {
            els.questionPosition.textContent = `Frage ${currentNumber} von ${state.total_questions}`;
        }

        if (els.questionText) {
            els.questionText.textContent = state.question.text || '';
        }

        if (els.questionTypeBadge) {
            els.questionTypeBadge.textContent = questionTypes[state.question.type] || state.question.type || '—';
            toggle(els.questionTypeBadge, true);
        }

        if (els.questionPoints) {
            els.questionPoints.textContent = `${state.question.points ?? 0} Punkte`;
        }

        if (els.questionCatalog) {
            if (state.question.catalog) {
                els.questionCatalog.textContent = state.question.catalog;
                toggle(els.questionCatalog, true);
            } else {
                els.questionCatalog.textContent = '';
                toggle(els.questionCatalog, false);
            }
        }

        if (els.questionImage && els.questionImageWrapper) {
            const nextImage = state.question.image_url || '';
            if (els.questionImage.getAttribute('src') !== nextImage) {
                els.questionImage.setAttribute('src', nextImage);
            }
            toggle(els.questionImageWrapper, !!nextImage);
        }

        renderAnswerForm(state);
    }

    applyState(state);

    createTransport({
        quizId: state.quiz_id,
        stateUrl,
        onState: applyState,
    }).start();
}