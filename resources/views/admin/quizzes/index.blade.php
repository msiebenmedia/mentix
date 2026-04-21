@extends('layouts.dashboard')

@section('title', 'Quizze')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-7xl mx-auto space-y-6">

        <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Quizze</h1>
                <p class="text-base-content/70 mt-1">
                    Erstelle und verwalte deine Quizze.
                </p>
            </div>

            <a href="{{ route('admin.quizzes.create') }}" class="btn btn-primary">
                <i class="ti ti-plus"></i>
                Neues Quiz
            </a>
        </div>

        @if (session('success'))
            <div class="alert alert-success shadow-sm">
                <i class="ti ti-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.quizzes.index') }}" class="grid grid-cols-1 md:grid-cols-[1fr_auto] gap-3">
                    <label class="form-control w-full">
                        <div class="label">
                            <span class="label-text">Suche</span>
                        </div>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Nach Titel suchen..."
                            class="input input-bordered w-full"
                        >
                    </label>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-search"></i>
                            Suchen
                        </button>

                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                            Zurücksetzen
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="card-body p-0">
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Status</th>
                                <th>Erstellt von</th>
                                <th>Erstellt am</th>
                                <th class="text-right">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($quizzes as $quiz)
                                @php
$statusClasses = [
    'draft' => 'badge badge-ghost',
    'live' => 'badge badge-success',
    'paused' => 'badge badge-warning',
    'ended' => 'badge badge-error',
];
                                @endphp

                                <tr>
                                    <td>
                                        <div class="font-semibold">{{ $quiz->title }}</div>

                                        @if ($quiz->description)
                                            <div class="text-sm text-base-content/60 line-clamp-2">
                                                {{ $quiz->description }}
                                            </div>
                                        @endif
                                    </td>

                                    <td>
                                        <span class="{{ $statusClasses[$quiz->status] ?? 'badge badge-ghost' }}">
                                            {{ $quiz->status_label }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $quiz->creator?->name ?? 'Unbekannt' }}
                                    </td>

                                    <td>
                                        {{ $quiz->created_at?->format('d.m.Y H:i') }}
                                    </td>

                                    <td>
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.quizzes.edit', $quiz) }}" class="btn btn-sm btn-primary">
                                                <i class="ti ti-pencil"></i>
                                                Bearbeiten
                                            </a>

                                            <form method="POST" action="{{ route('admin.quizzes.destroy', $quiz) }}" onsubmit="return confirm('Quiz wirklich löschen?');">
                                                @csrf
                                                @method('DELETE')

                                                <button type="submit" class="btn btn-sm btn-dark">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-base-content/60">
                                        Noch keine Quizze vorhanden.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div>
            {{ $quizzes->links() }}
        </div>
    </div>
</div>
@endsection