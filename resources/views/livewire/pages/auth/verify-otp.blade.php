<?php

use App\Models\User;
use App\Services\Auth\EmailOtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $code = '';
    public ?string $error = null;
    public ?string $notice = null;

    public ?User $pending = null;

    public function mount(): void
    {
        $id = session(EmailOtpService::sessionKey());

        // No pending registration means there is nothing to verify here.
        if (! $id || ! ($this->pending = User::find($id))) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if ($this->pending->hasVerifiedEmail()) {
            session()->forget(EmailOtpService::sessionKey());
            $this->redirect(route('login'), navigate: true);
        }
    }

    public function with(): array
    {
        return [
            'cooldown' => $this->pending
                ? app(EmailOtpService::class)->secondsUntilResend($this->pending)
                : 0,
        ];
    }

    public function verify(): void
    {
        $this->validate([
            'code' => ['required', 'digits:'.EmailOtpService::CODE_LENGTH],
        ], [
            'code.required' => 'Enter the code from your email.',
            'code.digits' => 'The code is '.EmailOtpService::CODE_LENGTH.' digits.',
        ]);

        $result = app(EmailOtpService::class)->verify($this->pending, $this->code);

        if (! $result['ok']) {
            $this->error = $result['error'];
            $this->notice = null;
            $this->code = '';

            return;
        }

        // Verified: only now is the session authenticated.
        session()->forget(EmailOtpService::sessionKey());

        Auth::login($this->pending, (bool) session(EmailOtpService::rememberKey(), false));
        session()->forget(EmailOtpService::rememberKey());

        Session::regenerate();

        session()->flash('status', 'Email verified. Welcome.');

        $this->redirect(route('dashboard', absolute: false), navigate: true);
    }

    public function resend(): void
    {
        $service = app(EmailOtpService::class);

        if (($wait = $service->secondsUntilResend($this->pending)) > 0) {
            $this->error = "Please wait {$wait} more seconds before requesting another code.";

            return;
        }

        $service->issue($this->pending);

        $this->error = null;
        $this->notice = 'A new code is on its way. The previous code no longer works.';
        $this->code = '';
    }

    public function cancel(): void
    {
        session()->forget(EmailOtpService::sessionKey());
        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div>
    <h1 class="text-2xl text-[#4a2c5a]">Verify your email</h1>

    <p class="mt-2 text-sm text-gray-600">
        We sent a {{ \App\Services\Auth\EmailOtpService::CODE_LENGTH }}-digit code to
        <strong class="text-gray-900">{{ $pending?->email }}</strong>.
        It expires in {{ \App\Services\Auth\EmailOtpService::EXPIRY_MINUTES }} minutes.
    </p>

    @if ($error)
        <div class="mt-4 rounded border-l-4 border-red-500 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $error }}
        </div>
    @endif

    @if ($notice)
        <div class="mt-4 rounded border-l-4 border-emerald-500 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ $notice }}
        </div>
    @endif

    <form wire:submit="verify" class="mt-6">
        <x-input-label for="code" :value="__('Verification code')" />

        <x-text-input wire:model="code" id="code" name="code"
                      type="text" inputmode="numeric" autocomplete="one-time-code"
                      maxlength="{{ \App\Services\Auth\EmailOtpService::CODE_LENGTH }}"
                      placeholder="000000"
                      class="mt-1 block w-full text-center text-2xl tracking-[0.5em] font-mono"
                      required autofocus />

        <x-input-error :messages="$errors->get('code')" class="mt-2" />

        <x-primary-button class="mt-4 w-full justify-center">
            {{ __('Verify and continue') }}
        </x-primary-button>
    </form>

    <div class="mt-6 flex items-center justify-between border-t border-gray-100 pt-4 text-sm">
        <button type="button" wire:click="resend"
                @disabled($cooldown > 0)
                class="text-gray-600 underline hover:text-gray-900 disabled:cursor-not-allowed disabled:text-gray-300 disabled:no-underline">
            @if ($cooldown > 0)
                Resend available in {{ $cooldown }}s
            @else
                Resend code
            @endif
        </button>

        <button type="button" wire:click="cancel" class="text-gray-500 underline hover:text-gray-800">
            Use a different account
        </button>
    </div>
</div>
