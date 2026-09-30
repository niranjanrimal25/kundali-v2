<?php

namespace App\Console\Commands;

use App\Mail\EmailVerificationCodeMail;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a real verification email so SMTP problems surface here rather
 * than when someone is trying to register.
 *
 * Gmail's common failure modes are all reported distinctly, because
 * "Connection could not be established" tells the user nothing useful.
 */
class TestMail extends Command
{
    protected $signature = 'mail:test {to? : Address to send to (defaults to MAIL_FROM_ADDRESS)}';

    protected $description = 'Send a test verification email to check the SMTP configuration';

    public function handle(): int
    {
        $to = $this->argument('to') ?: config('mail.from.address');

        $this->line('');
        $this->line('  Mailer   : <fg=cyan>'.config('mail.default').'</>');
        $this->line('  Host     : <fg=cyan>'.config('mail.mailers.smtp.host').':'.config('mail.mailers.smtp.port').'</>');
        $this->line('  Username : <fg=cyan>'.(config('mail.mailers.smtp.username') ?: '(not set)').'</>');
        $this->line('  From     : <fg=cyan>'.config('mail.from.address').'</>');
        $this->line('  To       : <fg=cyan>'.$to.'</>');
        $this->line('');

        foreach (['username' => config('mail.mailers.smtp.username'), 'from address' => config('mail.from.address')] as $label => $value) {
            if (! $value || str_contains((string) $value, 'PUT_YOUR_GMAIL')) {
                $this->error("The mail {$label} is not configured yet.");
                $this->line('  Set MAIL_USERNAME and MAIL_FROM_ADDRESS in .env to your Gmail address.');

                return self::FAILURE;
            }
        }

        $user = new User([
            'name' => 'Test Recipient',
            'email' => $to,
        ]);

        try {
            Mail::to($to)->send(new EmailVerificationCodeMail($user, '123456'));
        } catch (\Throwable $e) {
            $this->error('Sending failed.');
            $this->line('');
            $this->line('  <fg=yellow>'.$e->getMessage().'</>');
            $this->line('');
            $this->explain($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Sent. Check the inbox (and the spam folder) for code 123456.');

        if (config('mail.default') === 'log') {
            $this->warn('MAIL_MAILER is "log", so nothing actually left the server.');
            $this->line('  The message was written to storage/logs/laravel.log instead.');
        }

        return self::SUCCESS;
    }

    /** Translate the usual Gmail errors into the actual fix. */
    private function explain(string $message): void
    {
        $hints = [
            'Username and Password not accepted' => [
                'Gmail rejected the credentials. Almost always one of:',
                '  1. You used your normal Google password. It must be a 16-character App Password.',
                '  2. Two-factor authentication is off. App Passwords require 2FA to be enabled.',
                '  3. The app password was revoked, or belongs to a different account.',
                '  Create one at: https://myaccount.google.com/apppasswords',
            ],
            'Connection could not be established' => [
                'Could not reach smtp.gmail.com. Check outbound port 587 is not blocked',
                '  by a firewall, and that MAIL_HOST is smtp.gmail.com.',
            ],
            'Expected response code "250"' => [
                'The server rejected the sender address. MAIL_FROM_ADDRESS must be the',
                '  same Gmail account as MAIL_USERNAME — Gmail will not send as someone else.',
            ],
            'authentication failed' => [
                'Authentication failed. Confirm MAIL_USERNAME is the full address',
                '  including @gmail.com, and that the app password has no typos.',
            ],
        ];

        foreach ($hints as $needle => $lines) {
            if (stripos($message, $needle) !== false) {
                foreach ($lines as $line) {
                    $this->line('  '.$line);
                }

                return;
            }
        }

        $this->line('  Run with -v for the full stack trace.');
    }
}
