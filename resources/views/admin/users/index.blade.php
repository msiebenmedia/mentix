@extends('layouts.dashboard')

@section('title', 'Benutzerverwaltung')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-6xl mx-auto">

        <div class="mb-6">
            <h1 class="text-3xl font-bold">Benutzerverwaltung</h1>
            <p class="text-base-content/70 mt-1">
                Hier kannst du Benutzer verwalten und bearbeiten.
            </p>
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

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body overflow-x-auto">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Benutzername</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th class="text-right">Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{!! $user->is_active 
    ? '<div aria-label="status" class="status status-success"></div>' 
    : '<div aria-label="status" class="status status-error"></div>' 
!!} {{ $user->name }} </td>
                                <td>{{ $user->username }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @if($user->roles->isNotEmpty())
                                        <span class="badge badge-primary">
                                            {{ $user->roles->pluck('name')->join(', ') }}
                                        </span>
                                    @else
                                        <span class="badge badge-ghost">Keine Rolle</span>
                                    @endif
                                </td>
<td class="text-right">
    <div class="flex justify-end gap-2">

        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-primary">
            Bearbeiten
        </a>

        <form 
            method="POST" 
            action="{{ route('admin.users.destroy', $user) }}" 
            onsubmit="return confirm('Benutzer wirklich löschen?');"
        >
            @csrf
            @method('DELETE')

            <button type="submit" class="btn btn-sm ">
                <i class="ti ti-trash"></i>
            </button>
        </form>

    </div>
</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-base-content/60 py-6">
                                    Keine Benutzer gefunden.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

    </div>
</div>
@endsection