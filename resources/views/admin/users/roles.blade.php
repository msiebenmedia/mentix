@extends('layouts.dashboard')

@section('title', 'Rollenverwaltung')

@section('content')
<div class="min-h-screen bg-base-200 p-6">
    <div class="max-w-6xl mx-auto">
        <div class="mb-6">
            <h1 class="text-3xl font-bold">Rollenverwaltung</h1>
            <p class="text-base-content/70 mt-1">
                Hier kann der Admin die Rollen der Benutzer ändern.
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

        @if ($errors->any())
            <div class="alert alert-error mb-4">
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
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
                            <th>Aktuelle Rolle</th>
                            <th>Neue Rolle</th>
                            <th>Aktion</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
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
                                <td>
                                    <form method="POST" action="{{ route('admin.users.roles.update', $user) }}" class="flex items-center gap-2">
                                        @csrf

                                        <select name="role" class="select select-bordered w-full max-w-xs">
                                            @foreach($roles as $role)
                                                <option value="{{ $role->name }}"
                                                    @selected($user->hasRole($role->name))>
                                                    {{ ucfirst($role->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                </td>
                                <td>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            Speichern
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection