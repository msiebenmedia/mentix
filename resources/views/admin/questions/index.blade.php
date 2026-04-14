@extends('layouts.dashboard')

@section('title', 'Fragenverwaltung')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-7xl mx-auto">

        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Fragenverwaltung</h1>
                <p class="text-base-content/70 mt-1">
                    Hier kannst du Fragen anlegen, filtern, bearbeiten und löschen.
                </p>
            </div>

            <div>
                <a href="{{ route('admin.questions.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Neue Frage
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
                <form method="GET" action="{{ route('admin.questions.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Suche</span>
                            </label>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Nach Frage suchen..."
                                class="input input-bordered w-full"
                            >
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Katalog</span>
                            </label>
                            <select name="catalog" class="select select-bordered w-full">
                                <option value="">Alle</option>
                                @foreach($catalogs as $catalog)
                                    <option value="{{ $catalog->id }}" @selected(request('catalog') == $catalog->id)>
                                        {{ $catalog->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Typ</span>
                            </label>
                            <select name="type" class="select select-bordered w-full">
                                <option value="">Alle</option>
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}" @selected(request('type') === $value)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text">Status</span>
                            </label>
                            <select name="status" class="select select-bordered w-full">
                                <option value="">Alle</option>
                                <option value="active" @selected(request('status') === 'active')>Aktiv</option>
                                <option value="inactive" @selected(request('status') === 'inactive')>Inaktiv</option>
                            </select>
                        </div>

                    </div>

                    <div class="mt-4 flex justify-end gap-2">
                        <a href="{{ route('admin.questions.index') }}" class="btn btn-ghost">
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
                            <th>Frage</th>
                            <th>Katalog</th>
                            <th>Typ</th>
                            <th>Punkte</th>
                            <th>Status</th>
                            <th class="text-right">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($questions as $question)
                            <tr>
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
                                        {{ $types[$question->type] ?? $question->type }}
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

                                <td class="text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('admin.questions.edit', $question) }}" class="btn btn-sm btn-primary">
                                            Bearbeiten
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.questions.destroy', $question) }}"
                                            onsubmit="return confirm('Frage wirklich löschen?');"
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
                                <td colspan="6" class="text-center text-base-content/60 py-6">
                                    Keine Fragen gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $questions->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection