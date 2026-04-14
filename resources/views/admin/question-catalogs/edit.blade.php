@extends('layouts.dashboard')

@section('title', 'Fragenkatalog bearbeiten')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-3xl mx-auto">

        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold">Fragenkatalog bearbeiten</h1>
                <p class="text-base-content/70 mt-1">
                    Hier kannst du die Daten dieses Fragenkatalogs anpassen.
                </p>
            </div>

            <a href="{{ route('admin.question-catalogs.index') }}" class="btn btn-ghost">
                Zurück
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-6">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error mb-6">
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-error mb-6">
                <div>
                    <h3 class="font-bold">Es gibt Fehler im Formular:</h3>
                    <ul class="list-disc ml-5 mt-2 text-sm">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body">
                <h2 class="card-title mb-4">Katalogdaten</h2>

                <form method="POST" action="{{ route('admin.question-catalogs.update', $questionCatalog) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Titel</span>
                        </label>
                        <input
                            type="text"
                            name="title"
                            value="{{ old('title', $questionCatalog->title) }}"
                            class="input input-bordered w-full"
                            required
                        >
                    </div>

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">Beschreibung</span>
                        </label>
                        <textarea
                            name="description"
                            rows="5"
                            class="textarea textarea-bordered w-full"
                            placeholder="Optionale Beschreibung des Fragenkatalogs"
                        >{{ old('description', $questionCatalog->description) }}</textarea>
                    </div>

                    <div class="form-control w-full">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input
                                type="hidden"
                                name="is_active"
                                value="0"
                            >
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                class="toggle toggle-primary"
                                @checked(old('is_active', $questionCatalog->is_active))
                            >
                            <span class="label-text">Fragenkatalog ist aktiviert</span>
                        </label>
                    </div>

                    <div class="card-actions justify-end pt-4">
                        <button type="submit" class="btn btn-primary">
                            Änderungen speichern
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection