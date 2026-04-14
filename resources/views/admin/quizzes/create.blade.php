@extends('layouts.dashboard')

@section('title', 'Quiz erstellen')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-5xl mx-auto">

        <div class="mb-6">
            <h1 class="text-3xl font-bold">Quiz erstellen</h1>
            <p class="text-base-content/70 mt-1">
                Lege ein neues Quiz an und wähle Status, Layout und Spieler aus.
            </p>
        </div>

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <ul class="text-sm">
                    @foreach ($errors->all() as $error)
                        <li>• {{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.quizzes.store') }}">
            @csrf

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
                                value="{{ old('title') }}"
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
                                    <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>
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
                                    <option value="{{ $value }}" @selected(old('layout_template', 'classic') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Startzeit</span>
                            </label>
                            <input
                                type="datetime-local"
                                name="starts_at"
                                value="{{ old('starts_at') }}"
                                class="input input-bordered w-full"
                            >
                            <label class="label">
                                <span class="label-text-alt text-base-content/60">
                                    Nur relevant, wenn der Status auf „Geplant“ steht.
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
                                        @checked(collect(old('players', []))->contains($player->id))
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
                            Quiz erstellen
                        </button>
                    </div>

                </div>
            </div>
        </form>

    </div>
</div>
@endsection