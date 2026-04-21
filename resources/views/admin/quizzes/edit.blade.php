@extends('layouts.dashboard')

@section('title', 'Quiz bearbeiten')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-4xl mx-auto space-y-6">

        <div>
            <h1 class="text-3xl font-bold">Quiz bearbeiten</h1>
            <p class="text-base-content/70 mt-1">
                Aktuell bearbeitest du die Grunddaten dieses Quiz.
            </p>
        </div>

        @if (session('success'))
            <div class="alert alert-success shadow-sm">
                <i class="ti ti-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="card bg-base-100 shadow-sm border border-base-300">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}" class="space-y-5">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5">
                        <label class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Titel</span>
                            </div>
                            <input
                                type="text"
                                name="title"
                                value="{{ old('title', $quiz->title) }}"
                                class="input input-bordered w-full @error('title') input-error @enderror"
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
                            >{{ old('description', $quiz->description) }}</textarea>
                            @error('description')
                                <div class="label">
                                    <span class="label-text-alt text-error">{{ $message }}</span>
                                </div>
                            @enderror
                        </label>

                        @php
                            $statusClasses = [
                                'draft' => 'badge badge-ghost',
                                'scheduled' => 'badge badge-warning',
                                'live' => 'badge badge-success',
                                'ended' => 'badge badge-error',
                            ];
                        @endphp

                        <div class="form-control w-full">
                            <div class="label">
                                <span class="label-text">Status</span>
                            </div>

                            <div>
                                <span class="{{ $statusClasses[$quiz->status] ?? 'badge badge-ghost' }}">
                                    {{ $quiz->status_label }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy"></i>
                            Änderungen speichern
                        </button>

                        <a href="{{ route('admin.quizzes.index') }}" class="btn btn-ghost">
                            Zur Übersicht
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection