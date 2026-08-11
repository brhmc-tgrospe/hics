<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'username' => ['required_without:email', 'nullable', 'string'],
            'email' => ['required_without:username', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get the login identifier (either username or email).
     */
    public function loginIdentifier(): string
    {
        return (string) ($this->input('username') ?? $this->input('email'));
    }

    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = $this->loginIdentifier();
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $credentials = [
            $fieldType => $login,
            'password' => $this->input('password'),
        ];

        if (Auth::validate($credentials)) {
            $user = Auth::getProvider()->retrieveByCredentials($credentials);

            $sessionLifetime = config('session.lifetime') * 60;

            $hasActiveSession = \Illuminate\Support\Facades\DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('last_activity', '>=', now()->subSeconds($sessionLifetime)->getTimestamp())
                ->exists();

            if ($hasActiveSession && ! $this->boolean('confirm_logout')) {
                throw ValidationException::withMessages([
                    'confirm_logout' => 'This account is already logged in on another device.',
                ]);
            }
        }

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            $errorField = $this->has('username') ? 'username' : 'email';

            throw ValidationException::withMessages([
                $errorField => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());
        $errorField = $this->has('username') ? 'username' : 'email';

        throw ValidationException::withMessages([
            $errorField => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->loginIdentifier()).'|'.$this->ip());
    }
}
