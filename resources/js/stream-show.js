const state = window.streamState || {};
const statusLabels = window.quizStatusLabels || {};
const questionTypes = window.quizQuestionTypes || {};

const emptyState = document.getElementById("stream-empty");
const questionCard = document.getElementById("stream-question-card");
const pausedAlert = document.getElementById("stream-paused-alert");

const statusBadge = document.getElementById("stream-status-badge");
const progressBadge = document.getElementById("stream-progress-badge");
const pointsBadge = document.getElementById("stream-points-badge");
const catalogBadge = document.getElementById("stream-catalog-badge");
const typeBadge = document.getElementById("stream-type-badge");
const questionText = document.getElementById("stream-question-text");
const imageWrapper = document.getElementById("stream-image-wrapper");
const image = document.getElementById("stream-image");

function toggle(el, show) {
    if (!el) return;
    el.classList.toggle("hidden", !show);
}

function statusBadgeClass(status, paused = false) {
    if (paused) return "badge badge-warning";

    switch (status) {
        case "scheduled":
            return "badge badge-warning";
        case "live":
            return "badge badge-success";
        case "ended":
            return "badge badge-error";
        case "draft":
            return "badge badge-info";
        default:
            return "badge badge-ghost";
    }
}

function renderState(payload) {
    state.quiz_id = payload.quiz_id;
    state.status = payload.status;
    state.status_label = payload.status_label || statusLabels[payload.status] || payload.status;
    state.paused = !!payload.paused;
    state.current_index = payload.current_index;
    state.total_questions = payload.total_questions;
    state.revealed = !!payload.revealed;
    state.question = payload.question;

    toggle(pausedAlert, state.paused);

    if (!state.question) {
        toggle(emptyState, true);
        toggle(questionCard, false);
        return;
    }

    toggle(emptyState, false);
    toggle(questionCard, true);

    statusBadge.className = statusBadgeClass(state.status, state.paused);
    statusBadge.textContent = state.status_label;

    const currentNumber = state.current_index !== null && typeof state.current_index !== "undefined"
        ? Number(state.current_index) + 1
        : 0;

    progressBadge.textContent = `Frage ${currentNumber}/${state.total_questions ?? 0}`;

    if (state.question.points !== null && typeof state.question.points !== "undefined") {
        pointsBadge.textContent = `${state.question.points} Punkte`;
        toggle(pointsBadge, true);
    } else {
        pointsBadge.textContent = "";
        toggle(pointsBadge, false);
    }

    if (state.question.catalog) {
        catalogBadge.textContent = state.question.catalog;
        toggle(catalogBadge, true);
    } else {
        catalogBadge.textContent = "";
        toggle(catalogBadge, false);
    }

    if (state.question.type) {
        typeBadge.textContent = questionTypes[state.question.type] || state.question.type;
        toggle(typeBadge, true);
    } else {
        typeBadge.textContent = "";
        toggle(typeBadge, false);
    }

    questionText.textContent = state.question.text || "—";

    if (state.question.image_url) {
        image.src = state.question.image_url;
        toggle(imageWrapper, true);
    } else {
        image.src = "";
        toggle(imageWrapper, false);
    }
}

renderState(state);

if (window.Echo && state.quiz_id) {
    window.Echo.channel(`quiz.stream.${state.quiz_id}`)
        .listen(".quiz.stream.updated", (payload) => {
            renderState({
                ...payload,
            });
        });
}