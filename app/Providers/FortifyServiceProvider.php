<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

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
        // Deliberately NOT calling Fortify::ignoreRoutes() here, despite
        // Breeze's own routes/auth.php defining login/register/password
        // under the same route names Fortify would also register: that
        // call sets a process-wide static flag (Fortify::$registersRoutes)
        // that PHPUnit's single long-lived test process leaks across test
        // cases — whichever test boots the app first sees it true (Fortify
        // registers everything), every test after that inherits it already
        // false from the previous boot, so Fortify's two-factor/passkey
        // management routes (two-factor.enable, two-factor.qr-code, etc.
        // — never redefined by this app, so they only exist if Fortify
        // registers them) would silently vanish depending on test order.
        // Letting Fortify always register its full route table instead is
        // safe: Laravel's RouteCollection keys routes by method+URI, so
        // routes/auth.php's later registration of the exact same
        // login/register/password URIs (loaded after every provider has
        // booted) simply overwrites Fortify's entries for those — verified
        // via route:list, our own controllers still handle those requests.
        // The one real cost is config/fortify.php's own doc comment on
        // this: php artisan route:cache would throw on the duplicate
        // names — moot here since this app's deploy process never caches
        // routes (opcache staleness on the production FPM pool makes a
        // cached route table unreliable there regardless).
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        RateLimiter::for('passkeys', function (Request $request) {
            $credentialId = $request->input('credential.id');

            return Limit::perMinute(10)->by(
                ($credentialId ?: $request->session()->getId()).'|'.$request->ip()
            );
        });
    }
}
