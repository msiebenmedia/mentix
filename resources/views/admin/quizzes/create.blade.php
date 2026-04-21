@extends('layouts.dashboard')

@section('title', 'Quiz erstellen')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-4xl mx-auto space-y-6">

        <div>
            <h1 class="text-3xl font-bold">Quiz erstellen</h1>
            <p class="text-base-content/70 mt-1">
                Lege ein neues Quiz an. Es startet automatisch im Status „Nicht gestartet“.
            </p>
        </div>

        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.quizzes.store') }}" class="space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Titel</span>
                            </div>
                            <input
                                type="text"
                                name="title"
                                value="{{ old('title') }}"
                                class="input input-bordered w-full @error('title') input-error @enderror"
                                placeholder="z. B. Allgemeinwissen #1"
                                required
                            >
                            @error('title')
                                <div class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </div>
                            @enderror
                        </label>

                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Beschreibung</span>
                            </div>
                            <textarea
                                name="description"
                                rows="6"
                                class="textarea textarea-bordered w-full @error('description') textarea-error @enderror"
                                placeholder="Kurze Beschreibung des Quiz..."
                            >{{ old('description') }}</textarea>
                            @error('description')
                                <div class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </div>
                            @enderror
                        </label>

                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Status</span>
                            </div>
                            <input
                                type="text"
                                value="Nicht gestartet"
                                class="input input-bordered w-full"
                                disabled
                            >
                        </label>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i>
                            Quiz erstellen
                        </button>

                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                            Abbrechen
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection