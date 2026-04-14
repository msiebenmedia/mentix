@extends('layouts.dashboard')

@section('title', 'Quiz bearbeiten')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-6xl mx-auto">

        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Quiz bearbeiten</h1>
                <p class="text-base-content/70 mt-1">
                    Bearbeite Einstellungen und verwalte die Fragen dieses Quiz.
                </p>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                    <i class="ti ti-arrow-left"></i>
                    Zurück
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-4">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error mb-4">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <ul class="text-sm">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $activeTab = old('tab', request('tab', 'settings'));

            $selectedQuestionIds = collect(old('question_ids', $quiz->questions->pluck('id')->toArray() ?? []))
                ->map(fn ($id) => (int) $id)
                ->all();

            $statusClasses = [
                'draft' => 'badge badge-ghost',
                'scheduled' => 'badge badge-warning',
                'live' => 'badge badge-success',
                'ended' => 'badge badge-error',
            ];
        @endphp

        <div class="tabs tabs-box mb-6 w-fit bg-base-100 border border-base-300 shadow-sm">
            <a
                href="{{ route('admin.quizzes.edit', ['quiz' => $quiz->id, 'tab' => 'settings']) }}"
                class="tab {{ $activeTab === 'settings' ? 'tab-active' : '' }}"
            >
                <i class="ti ti-settings mr-2"></i>
                Einstellungen
            </a>

            <a
                href="{{ route('admin.quizzes.edit', ['quiz' => $quiz->id, 'tab' => 'questions']) }}"
                class="tab {{ $activeTab === 'questions' ? 'tab-active' : '' }}"
            >
                <i class="ti ti-list-check mr-2"></i>
                Fragen verwalten
            </a>
        </div>

        @if($activeTab === 'settings')
            <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}">
                @csrf
                @method('PUT')

                <input type="hidden" name="tab" value="settings">

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                            <div class="form-control md:col-span-2">
                                <label class="label">
                                    <span class="label-text">Titel</span>
                                </label>
                                <input
                                    type="text"
                                    name="title"
                                    value="{{ old('title', $quiz->title) }}"
                                    placeholder="z. B. Freitagabend Quizshow"
                                    class="input input-bordered w-full"
                                    required
                                >
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Status</span>
                                </label>
                                <select name="status" class="select select-bordered w-full" required>
                                    @foreach($statuses as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $quiz->status) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Layout</span>
                                </label>
                                <select name="layout_template" class="select select-bordered w-full" required>
                                    @foreach($templates as $value => $label)
                                        <option value="{{ $value }}" @selected(old('layout_template', $quiz->layout_template) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Startzeit</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    name="starts_at"
                                    value="{{ old('starts_at', optional($quiz->starts_at)->format('Y-m-d\TH:i')) }}"
                                    class="input input-bordered w-full"
                                >
                                <label class="label">
                                    <span class="label-text-alt text-base-content/60">
                                        Nur relevant, wenn der Status auf „Geplant“ steht.
                                    </span>
                                </label>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Beendet am</span>
                                </label>
                                <input
                                    type="datetime-local"
                                    name="ended_at"
                                    value="{{ old('ended_at', optional($quiz->ended_at)->format('Y-m-d\TH:i')) }}"
                                    class="input input-bordered w-full"
                                >
                                <label class="label">
                                    <span class="label-text-alt text-base-content/60">
                                        Optional. Sinnvoll, wenn ein Quiz bereits abgeschlossen wurde.
                                    </span>
                                </label>
                            </div>

                        </div>

                        <div class="divider">Spieler</div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Teilnehmer auswählen</span>
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($players as $player)
                                    <label class="border border-base-300 rounded-xl p-3 flex items-center gap-3 cursor-pointer hover:bg-base-200 transition">
                                        <input
                                            type="checkbox"
                                            name="players[]"
                                            value="{{ $player->id }}"
                                            class="checkbox checkbox-primary"
                                            @checked(collect(old('players', $quiz->players->pluck('id')->toArray()))->contains($player->id))
                                        >

                                        <div class="avatar placeholder">
                                            <div class="bg-primary text-primary-content rounded-full w-10">
                                                <span class="text-sm font-semibold">
                                                    {{ strtoupper(substr($player->name ?? 'U', 0, 1)) }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="font-medium truncate">
                                                {{ $player->name }}
                                            </div>

                                            @if(!empty($player->email))
                                                <div class="text-xs text-base-content/60 truncate">
                                                    {{ $player->email }}
                                                </div>
                                            @endif
                                        </div>
                                    </label>
                                @empty
                                    <div class="text-base-content/60">
                                        Keine Spieler verfügbar.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        <div class="mt-6 flex justify-end gap-2">
                            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                                <i class="ti ti-arrow-left"></i>
                                Zurück
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i>
                                Änderungen speichern
                            </button>
                        </div>

                    </div>
                </div>
            </form>
        @endif

        @if($activeTab === 'questions')

            {{-- Bereits ausgewählte Fragen --}}
            <div class="card bg-base-100 shadow-xl border border-base-300 mb-6">
                <div class="card-body">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                        <div>
                            <h2 class="card-title">Ausgewählte Fragen</h2>
                            <p class="text-sm text-base-content/70">
                                Diese Fragen sind aktuell dem Quiz zugewiesen.
                            </p>
                        </div>

                        <span class="badge badge-outline">
                            {{ $quiz->questions->count() }} ausgewählt
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Frage</th>
                                    <th>Katalog</th>
                                    <th>Typ</th>
                                    <th>Punkte</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quiz->questions as $question)
                                    <tr>
                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ $question->pivot->sort_order ?? $loop->iteration }}
                                            </span>
                                        </td>

                                        <td class="max-w-xl">
                                            <div class="font-medium line-clamp-2">
                                                {{ $question->question }}
                                            </div>
                                        </td>

                                        <td>
                                            @if($question->catalog)
                                                <span class="badge badge-outline">
                                                    {{ $question->catalog->title }}
                                                </span>
                                            @else
                                                <span class="badge badge-ghost">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge badge-primary">
                                                {{ $questionTypes[$question->type] ?? $question->type }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ $question->points }}
                                            </span>
                                        </td>

                                        <td>
                                            @if($question->is_active)
                                                <span class="badge badge-success">Aktiv</span>
                                            @else
                                                <span class="badge badge-ghost">Inaktiv</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-base-content/60 py-6">
                                            Diesem Quiz sind aktuell noch keine Fragen zugewiesen.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($quiz->questions->count() > 0)
                        <div class="mt-4 text-sm text-base-content/60">
                            Die Reihenfolge entspricht aktuell der Auswahl-Reihenfolge beim Speichern.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Filter --}}
            <div class="card bg-base-100 shadow-xl border border-base-300 mb-6">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.quizzes.edit', $quiz) }}">
                        <input type="hidden" name="tab" value="questions">

                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                            <div class="form-control md:col-span-2">
                                <label class="label">
                                    <span class="label-text">Suche</span>
                                </label>
                                <input
                                    type="text"
                                    name="question_search"
                                    value="{{ request('question_search') }}"
                                    placeholder="Nach Frage suchen..."
                                    class="input input-bordered w-full"
                                >
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Katalog</span>
                                </label>
                                <select name="question_catalog" class="select select-bordered w-full">
                                    <option value="">Alle</option>
                                    @foreach($catalogs as $catalog)
                                        <option value="{{ $catalog->id }}" @selected(request('question_catalog') == $catalog->id)>
                                            {{ $catalog->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Typ</span>
                                </label>
                                <select name="question_type" class="select select-bordered w-full">
                                    <option value="">Alle</option>
                                    @foreach($questionTypes as $value => $label)
                                        <option value="{{ $value }}" @selected(request('question_type') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-control">
                                <label class="label">
                                    <span class="label-text">Status</span>
                                </label>
                                <select name="question_status" class="select select-bordered w-full">
                                    <option value="">Alle</option>
                                    <option value="active" @selected(request('question_status') === 'active')>Aktiv</option>
                                    <option value="inactive" @selected(request('question_status') === 'inactive')>Inaktiv</option>
                                </select>
                            </div>

                        </div>

                        <div class="mt-4 flex justify-end gap-2">
                            <a href="{{ route('admin.quizzes.edit', ['quiz' => $quiz->id, 'tab' => 'questions']) }}" class="btn btn-ghost">
                                <i class="ti ti-refresh"></i>
                                Zurücksetzen
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-search"></i>
                                Filtern
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Auswahl + Speichern --}}
            <form method="POST" action="{{ route('admin.quizzes.questions.update', $quiz) }}">
                @csrf
                @method('PUT')

                <input type="hidden" name="tab" value="questions">

                <div class="card bg-base-100 shadow-xl border border-base-300">
                    <div class="card-body overflow-x-auto">
                        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-4">
                            <div>
                                <h2 class="card-title">Fragen auswählen</h2>
                                <p class="text-sm text-base-content/70">
                                    Wähle aus, welche Fragen in diesem Quiz verwendet werden sollen.
                                </p>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i>
                                Fragen speichern
                            </button>
                        </div>

                        <table class="table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Frage</th>
                                    <th>Katalog</th>
                                    <th>Typ</th>
                                    <th>Punkte</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($availableQuestions as $question)
                                    <tr>
                                        <td>
                                            <input
                                                type="checkbox"
                                                name="question_ids[]"
                                                value="{{ $question->id }}"
                                                class="checkbox checkbox-primary"
                                                @checked(in_array($question->id, $selectedQuestionIds))
                                            >
                                        </td>

                                        <td class="max-w-xl">
                                            <div class="flex items-start gap-2">
                                                {!! $question->is_active
                                                    ? '<div aria-label="status" class="status status-success mt-1"></div>'
                                                    : '<div aria-label="status" class="status status-error mt-1"></div>'
                                                !!}

                                                <div>
                                                    <div class="font-medium line-clamp-2">
                                                        {{ $question->question }}
                                                    </div>

                                                    @if($question->type === \App\Models\Question::TYPE_IMAGE_CHOICE && $question->image_path)
                                                        <div class="text-xs text-base-content/60 mt-1">
                                                            <i class="ti ti-photo text-sm"></i>
                                                            Bild hinterlegt
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>

                                        <td>
                                            @if($question->catalog)
                                                <span class="badge badge-outline">
                                                    {{ $question->catalog->title }}
                                                </span>
                                            @else
                                                <span class="badge badge-ghost">—</span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="badge badge-primary">
                                                {{ $questionTypes[$question->type] ?? $question->type }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="badge badge-secondary">
                                                {{ $question->points }}
                                            </span>
                                        </td>

                                        <td>
                                            @if($question->is_active)
                                                <span class="badge badge-success">
                                                    Aktiv
                                                </span>
                                            @else
                                                <span class="badge badge-ghost">
                                                    Inaktiv
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-base-content/60 py-6">
                                            Keine Fragen gefunden.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        <div class="mt-6 flex justify-end gap-2">
                            <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                                <i class="ti ti-arrow-left"></i>
                                Zurück
                            </a>

                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy"></i>
                                Fragen speichern
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif

    </div>
</div>
@endsection