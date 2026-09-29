<?php

use App\Models\User;
use App\Rules\StrongPassword;
use App\Services\Auth\EmailOtpService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Live checklist state for the UI. The same rule object drives this
     * and the server-side validation, so the tick marks can never
     * disagree with what the backend will accept.
     */
    public function with(): array
    {
        return [
            'requirements' => StrongPassword::requirements(),
            'met' => StrongPassword::check($this->password),
            'confirmMatches' => $this->password !== ''
                && $this->password === $this->password_confirmation,
        ];
    }

    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        event(new Registered($user));

        // The account exists but is NOT logged in. Access is granted only
        // after the emailed code is confirmed.
        app(EmailOtpService::class)->issue($user);

        session()->put(EmailOtpService::sessionKey(), $user->id);

        $this->redirect(route('verification.otp'), navigate: true);
    }
}; ?>

<div>
    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
            <p class="mt-1 text-xs text-gray-500">
                We will send a 6-digit code here. The address must be reachable.
            </p>
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model.live.debounce.200ms="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />

            {{-- Live requirement checklist --}}
            <ul class="mt-3 space-y-1.5 rounded border border-gray-200 bg-gray-50 p-3">
                @foreach ($requirements as $key => $label)
                    <li class="flex items-center gap-2 text-xs {{ $met[$key] ? 'text-emerald-700' : 'text-gray-500' }}">
                        @if ($met[$key])
                            <svg class="h-4 w-4 flex-none text-emerald-600" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.7 5.3a1 1 0 0 1 0 1.4l-7.5 7.5a1 1 0 0 1-1.4 0L3.3 9.7a1 1 0 1 1 1.4-1.4l3.8 3.8 6.8-6.8a1 1 0 0 1 1.4 0Z" clip-rule="evenodd"/>
                            </svg>
                        @else
                            <svg class="h-4 w-4 flex-none text-gray-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <circle cx="10" cy="10" r="7" fill="none" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        @endif
                        <span>{{ $label }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model.live.debounce.200ms="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />

            @if ($password_confirmation !== '')
                <p class="mt-1 text-xs {{ $confirmMatches ? 'text-emerald-700' : 'text-red-600' }}">
                    {{ $confirmMatches ? 'Passwords match.' : 'Passwords do not match yet.' }}
                </p>
            @endif
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</div>
