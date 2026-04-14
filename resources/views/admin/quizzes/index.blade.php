@extends('layouts.dashboard')

@section('title', 'Quizverwaltung')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-7xl mx-auto">

        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Quizverwaltung</h1>
                <p class="text-base-content/70 mt-1">
                    Hier kannst du Quizze anlegen, filtern, bearbeiten, neustarten und löschen.
                </p>
            </div>

            <div>
                <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Neues Quiz
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

        {{-- Filter --}}
        <div class="card bg-base-100 shadow-xl border border-base-300 mb-6">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.quizzes.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Suche</span>
                            </label>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Nach Quiztitel suchen..."
                                class="input input-bordered w-full"
                            >
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Status</span>
                            </label>
                            <select name="status" class="select select-bordered w-full">
                                <option value="">Alle</option>
                                @foreach($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected(request('status') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
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

        {{-- Tabelle --}}
        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Status</th>
                            <th>Spieler</th>
                            <th>Layout</th>
                            <th>Startzeit</th>
                            <th>Erstellt von</th>
                            <th class="text-right">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($quizzes as $quiz)
                            <tr>
                                <td class="max-w-xl">
                                    <div class="font-medium line-clamp-2">
                                        {{ $quiz->title }}
                                    </div>
                                </td>

                                <td>
                                    @php
                                        $statusClasses = [
                                            'draft' => 'badge badge-ghost',
                                            'scheduled' => 'badge badge-warning',
                                            'live' => 'badge badge-success',
                                            'ended' => 'badge badge-error',
                                        ];
                                    @endphp

                                    <span class="{{ $statusClasses[$quiz->status] ?? 'badge badge-ghost' }}">
                                        {{ $statuses[$quiz->status] ?? $quiz->status }}
                                    </span>
                                </td>

                                <td>
                                    <div class="flex -space-x-2 items-center">
                                        @foreach($quiz->players->take(5) as $player)
                                            <div class="avatar">
                                                <div class="w-8 rounded-full bg-primary text-primary-content flex items-center justify-center text-xs">
                                                    {{ strtoupper(substr($player->name, 0, 1)) }}
                                                </div>
                                            </div>
                                        @endforeach

                                        @if($quiz->players->count() > 5)
                                            <div class="ml-3 text-xs text-base-content/60">
                                                +{{ $quiz->players->count() - 5 }} weitere
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td>
                                    <span class="badge badge-outline">
                                        {{ $templates[$quiz->layout_template] ?? $quiz->layout_template }}
                                    </span>
                                </td>

                                <td>
                                    @if($quiz->starts_at)
                                        <span class="text-sm">
                                            {{ $quiz->starts_at->format('d.m.Y H:i') }}
                                        </span>
                                    @else
                                        <span class="text-base-content/60">—</span>
                                    @endif
                                </td>

                                <td>
                                    {{ $quiz->creator->username ?? '—' }}
                                </td>

                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-sm btn-primary">
                                            Bearbeiten
                                        </a>

                                        <a href="{{ route('admin.quizzes.host', $quiz) }}" class="btn btn-sm">
                                            <i class="ti ti-player-play"></i>
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.quizzes.restart', $quiz) }}"
                                            onsubmit="return confirm('Quiz wirklich neustarten? Fortschritt und abgegebene Antworten werden zurückgesetzt.');"
                                        >
                                            @csrf

                                            <button type="submit" class="btn btn-sm btn-warning" title="Quiz neustarten">
                                                <i class="ti ti-rotate-clockwise"></i>
                                            </button>
                                        </form>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.quizzes.destroy', $quiz) }}"
                                            onsubmit="return confirm('Quiz wirklich löschen?');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-base-content/60 py-6">
                                    Keine Quizze gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $quizzes->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection