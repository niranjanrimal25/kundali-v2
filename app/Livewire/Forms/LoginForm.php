<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Services\Auth\EmailOtpService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /** Set when credentials were correct but the email is unverified. */
    public bool $requiresVerification = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        // Validate the credentials WITHOUT starting a session, so an
        // unverified account is never logged in even momentarily.
        if (! Auth::validate($this->only(['email', 'password']))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = User::where('email', $this->email)->firstOrFail();

        if (! $user->hasVerifiedEmail()) {
            // Correct password, unverified address: issue a fresh code
            // and divert to verification rather than granting access.
            $this->requiresVerification = true;

            app(EmailOtpService::class)->issue($user);

            session()->put(EmailOtpService::sessionKey(), $user->id);
            session()->put(EmailOtpService::rememberKey(), $this->remember);

            return;
        }

        Auth::login($user, $this->remember);
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
