@extends('layouts.dashboard')

@section('title', 'Mein Profil')

@section('content')
<div class="min-h-screen bg-base-200 py-8 px-4">
    <div class="max-w-3xl mx-auto">

        <div class="mb-6">
            <h1 class="text-3xl font-bold">Mein Profil</h1>
            <p class="text-base-content/70 mt-1">
                Hier kannst du deine persönlichen Daten bearbeiten.
            </p>
        </div>

        @if(session('success'))
            <div class="alert alert-success mb-6">
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error mb-6">
                <div>
                    <h3 class="font-bold">Es gibt Fehler im Formular:</h3>
                    <ul class="list-disc ml-5 mt-2 text-sm">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <div class="card bg-base-100 shadow-xl border border-base-300">
            <div class="card-body">
                <h2 class="card-title mb-4">Profil bearbeiten</h2>

                <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Name</span>
                            </label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $user->name) }}"
                                class="input input-bordered w-full"
                                required
                            >
                        </div>

                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Benutzername</span>
                            </label>
                            <input
                                type="text"
                                name="username"
                                value="{{ old('username', $user->username) }}"
                                class="input input-bordered w-full"
                                disabled
                            >
                        </div>
                    </div>

                    <div class="form-control w-full">
                        <label class="label">
                            <span class="label-text">E-Mail</span>
                        </label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email', $user->email) }}"
                            class="input input-bordered w-full"
                            required
                        >
                    </div>

                    <div class="divider">Passwort ändern (optional)</div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Neues Passwort</span>
                            </label>
                            <input
                                type="password"
                                name="password"
                                class="input input-bordered w-full"
                                placeholder="Leer lassen, wenn es gleich bleiben soll"
                            >
                        </div>

                        <div class="form-control w-full">
                            <label class="label">
                                <span class="label-text">Passwort wiederholen</span>
                            </label>
                            <input
                                type="password"
                                name="password_confirmation"
                                class="input input-bordered w-full"
                            >
                        </div>
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