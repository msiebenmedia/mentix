@extends('layouts.dashboard')

@section('title', 'Fragenkataloge')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-6xl mx-auto">

        <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
            <div>
                <h1 class="text-3xl font-bold">Fragenkataloge</h1>
                <p class="text-base-content/70 mt-1">
                    Hier kannst du Fragenkataloge anlegen, bearbeiten und löschen.
                </p>
            </div>

            <div>
                <a href="{{ route('admin.question-catalogs.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus"></i>
                    Neuer Katalog
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
                <form method="GET" action="{{ route('admin.question-catalogs.index') }}">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">

                        <div class="form-control md:col-span-2">
                            <label class="label">
                                <span class="label-text">Suche</span>
                            </label>
                            <input
                                type="text"
                                name="search"
                                value="{{ request('search') }}"
                                placeholder="Nach Titel suchen..."
                                class="input input-bordered w-full"
                            >
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

                        <div class="form-control">
                            <label class="label opacity-0">
                                <span class="label-text">Aktionen</span>
                            </label>
                            <div class="flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-search"></i>
                                    Filtern
                                </button>

                                <a href="{{ route('admin.question-catalogs.index') }}" class="btn ">
                                    <i class="ti ti-refresh"></i>
                                </a>
                            </div>
                        </div>

                    </div>
                </form>
            </div>
        </div>

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>Beschreibung</th>
                            <th>Fragen</th>
                            <th>Status</th>
                            <th class="text-right">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($catalogs as $catalog)
                            <tr>
                                <td>
                                    {!! $catalog->is_active
                                        ? '<div aria-label="status" class="status status-success"></div>'
                                        : '<div aria-label="status" class="status status-error"></div>'
                                    !!}
                                    {{ $catalog->title }}
                                </td>

                                <td class="max-w-md">
                                    <div class="truncate">
                                        {{ $catalog->description ?: '—' }}
                                    </div>
                                </td>

                                <td>
                                    <span class="badge badge-primary">
                                        {{ $catalog->questions_count }}
                                    </span>
                                </td>

                                <td>
                                    @if($catalog->is_active)
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
                                        <a href="{{ route('admin.question-catalogs.edit', $catalog) }}" class="btn btn-sm btn-primary">
                                            Bearbeiten
                                        </a>

                                        <form
                                            method="POST"
                                            action="{{ route('admin.question-catalogs.destroy', $catalog) }}"
                                            onsubmit="return confirm('Fragenkatalog wirklich löschen?{{ $catalog->questions_count > 0 ? ' Alle enthaltenen Fragen werden ebenfalls gelöscht.' : '' }}');"
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
                                <td colspan="5" class="text-center text-base-content/60 py-6">
                                    Keine Fragenkataloge gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $catalogs->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection