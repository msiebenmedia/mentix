@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <div class="w-full max-w-md">
        <div class="card bg-base-100 shadow-2xl border border-base-300">
            <div class="card-body">


                @if (session('status'))
                    <div class="alert alert-success mb-4">
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error mb-4">
                        <ul class="text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                <div class="form-control">
                    <label for="login" class="label">
                        <span class="label-text">E-Mail oder Benutzername</span>
                    </label>
                    <input
                        id="login"
                        type="text"
                        name="email"
                        value="{{ old('email') }}"
                        class="input input-bordered w-full"
                        placeholder="E-Mail oder Benutzername"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>

                    <div class="form-control">
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="label p-0">
                                <span class="label-text">Passwort</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="link link-hover text-sm">
                                    Passwort vergessen?
                                </a>
                            @endif
                        </div>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="input input-bordered w-full"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input
                                type="checkbox"
                                name="remember"
                                class="checkbox checkbox-primary checkbox-sm"
                            >
                            <span class="label-text">Angemeldet bleiben</span>
                        </label>
                    </div>

                    <div class="form-control pt-2">
                        <button type="submit" class="btn btn-primary w-full">
                            Login
                        </button>
                    </div>
                </form>

                @if (Route::has('register'))
                    <div class="divider my-6">oder</div>

                    <div class="text-center">
                        <p class="text-sm text-base-content/70">
                            Noch kein Konto?
                        </p>
                        <a href="{{ route('register') }}" class="btn  w-full mt-3">
                            Jetzt registrieren
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection