<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\SuccessfulPasswordResetLinkRequestResponse;

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

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {
            public function toResponse($request)
            {
                return new JsonResponse(['success' => true, 'message' => 'Logged in'], 200);
            }
        });

        // ログアウト成功時のレスポンスをJSONに上書き
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse {
            public function toResponse($request)
            {
                return new JsonResponse(['success' => true, 'message' => 'Logged out'], 200);
            }
        });

        // 新規登録成功時のレスポンスをJSONに上書き
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse {
            public function toResponse($request)
            {
                return new JsonResponse(['success' => true, 'message' => 'Registered'], 201);
            }
        });

        $this->app->instance(SuccessfulPasswordResetLinkRequestResponse::class, new class implements SuccessfulPasswordResetLinkRequestResponse {
            public function toResponse($request)
            {
                return new JsonResponse(['success' => true, 'message' => 'Password reset link sent'], 200);
            }
        });

        $this->app->instance(PasswordResetResponse::class, new class implements PasswordResetResponse {
            public function toResponse($request)
            {
                return new JsonResponse(['success' => true, 'message' => 'Password has been reset'], 200);
            }
        });

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return "http://localhost:5173/reset-password?token={$token}&email={$user->email}";
        });
    }
}
