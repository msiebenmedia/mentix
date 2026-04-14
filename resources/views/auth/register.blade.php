@extends('layouts.auth')

@section('title', 'Registrieren')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-base-200 px-4 py-10">



    <div class="card w-full max-w-md bg-base-100 shadow-xl relative z-10">
        <div class="card-body">
            <h2 class="text-2xl font-bold text-center mb-2">
                Registrieren
            </h2>

            <p class="text-sm text-base-content/70 text-center mb-6">
                Erstelle deinen Account für {{ config('app.name') }}
            </p>

            {{-- Fehleranzeige --}}
            @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <div class="text-sm">
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-4">
                @csrf
{{-- Username --}}
<div class="form-control">
    <label class="label" for="username">
        <span class="label-text">Username</span>
    </label>
    <input
        id="username"
        type="text"
        name="username"
        value="{{ old('username') }}"
        required
        autocomplete="username"
        class="input input-bordered w-full @error('username') input-error @enderror"
        placeholder="Dein Username"
    >
</div>
                {{-- Name --}}
                <div class="form-control">
                    <label class="label" for="name">
                        <span class="label-text">Name</span>
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        required
                        autofocus
                        autocomplete="name"
                        class="input input-bordered w-full @error('name') input-error @enderror"
                        placeholder="Dein Name"
                    >
                </div>

                {{-- E-Mail --}}
                <div class="form-control">
                    <label class="label" for="email">
                        <span class="label-text">E-Mail</span>
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autocomplete="username"
                        class="input input-bordered w-full @error('email') input-error @enderror"
                        placeholder="deine@email.de"
                    >
                </div>

                {{-- Passwort --}}
                <div class="form-control">
                    <label class="label" for="password">
                        <span class="label-text">Passwort</span>
                    </label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="input input-bordered w-full @error('password') input-error @enderror"
                        placeholder="••••••••"
                    >
                </div>

                {{-- Passwort bestätigen --}}
                <div class="form-control">
                    <label class="label" for="password_confirmation">
                        <span class="label-text">Passwort bestätigen</span>
                    </label>
                    <input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="input input-bordered w-full"
                        placeholder="••••••••"
                    >
                </div>

                {{-- Submit --}}
                <div class="form-control pt-2">
                    <button type="submit" class="btn btn-primary w-full">
                        Account erstellen
                    </button>
                </div>
            </form>

            <div class="divider my-4">oder</div>

            <p class="text-sm text-center text-base-content/70">
                Du hast schon einen Account?
                <a href="{{ route('login') }}" class="link link-primary font-medium">
                    Jetzt einloggen
                </a>
            </p>
        </div>
    </div>
</div>
@endsection