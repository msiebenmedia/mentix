<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Illuminate\Validation\ValidationException;


class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::loginView(function () {
            return view('auth.login');
        });

        Fortify::registerView(function () {
            return view('auth.register');
        });

Fortify::authenticateUsing(function (Request $request) {
    $request->validate([
        'email' => ['required', 'string'],
        'password' => ['required', 'string'],
    ]);

    $login = trim((string) $request->input('email'));

    $user = User::query()
        ->whereRaw('LOWER(email) = ?', [Str::lower($login)])
        ->orWhereRaw('LOWER(username) = ?', [Str::lower($login)])
        ->first();

    if (! $user) {
        return null;
    }

    if (! Hash::check($request->input('password'), $user->password)) {
        return null;
    }

    if (! $user->is_active) {
        throw ValidationException::withMessages([
            Fortify::username() => 'Dein Account wurde noch nicht freigeschaltet.',
        ]);
    }

    return $user;
});

        RateLimiter::for('login', function (Request $request) {
            $login = (string) $request->input('email', '');

            $throttleKey = Str::transliterate(
                Str::lower($login) . '|' . $request->ip()
            );

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}