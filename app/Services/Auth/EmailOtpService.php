<?php

namespace App\Services\Auth;

use App\Mail\EmailVerificationCodeMail;
use App\Models\EmailVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Issues and checks the six-digit email verification codes.
 *
 * Security decisions, all deliberate:
 *
 *  - Codes are HASHED at rest, like passwords. A database leak must not
 *    hand out working codes.
 *  - Issuing a new code invalidates every previous unused one, so an old
 *    email cannot be replayed.
 *  - Attempts are capped per code, which is what actually stops brute
 *    force: six digits is only a million combinations.
 *  - Resends are rate limited by a cooldown, so the endpoint cannot be
 *    used to spam somebody's inbox.
 */
class EmailOtpService
{
    public const CODE_LENGTH = 6;

    public const EXPIRY_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    /** Generate, store and email a fresh code. */
    public function issue(User $user): string
    {
        // Any code still outstanding is retired the moment a new one is
        // issued, so only the newest email ever works.
        EmailVerificationCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode();

        EmailVerificationCode::create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        Mail::to($user->email)->send(new EmailVerificationCodeMail($user, $code));

        return $code;
    }

    /** The newest usable code for this user, if any. */
    public function current(User $user): ?EmailVerificationCode
    {
        return EmailVerificationCode::where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->latest('id')
            ->first();
    }

    /** Seconds remaining before another code may be requested. */
    public function secondsUntilResend(User $user): int
    {
        $latest = EmailVerificationCode::where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $latest) {
            return 0;
        }

        $elapsed = $latest->created_at->diffInSeconds(now());

        return (int) max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    /**
     * Check a submitted code.
     *
     * @return array{ok:bool, error:?string}
     */
    public function verify(User $user, string $submitted): array
    {
        $record = $this->current($user);

        if (! $record) {
            return ['ok' => false, 'error' => 'No verification code is outstanding. Please request a new one.'];
        }

        if ($record->isExpired()) {
            return ['ok' => false, 'error' => 'That code has expired. Please request a new one.'];
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            // Burn the code rather than leaving it guessable.
            $record->update(['consumed_at' => now()]);

            return ['ok' => false, 'error' => 'Too many incorrect attempts. Please request a new code.'];
        }

        $record->increment('attempts');

        if (! Hash::check(trim($submitted), $record->code_hash)) {
            $remaining = self::MAX_ATTEMPTS - $record->attempts;

            return [
                'ok' => false,
                'error' => $remaining > 0
                    ? "That code is not correct. {$remaining} attempt".($remaining === 1 ? '' : 's').' remaining.'
                    : 'Too many incorrect attempts. Please request a new code.',
            ];
        }

        $record->update(['consumed_at' => now()]);

        $user->forceFill(['email_verified_at' => now()])->save();

        return ['ok' => true, 'error' => null];
    }

    /** Cryptographically random, and always the full length. */
    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    /** Used to key the pending-verification session entry. */
    public static function sessionKey(): string
    {
        return 'auth.otp.pending_user';
    }

    public static function rememberKey(): string
    {
        return 'auth.otp.remember';
    }

    public static function newToken(): string
    {
        return Str::random(40);
    }
}
