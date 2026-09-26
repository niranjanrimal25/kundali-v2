<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Builds the Swiss Ephemeris `swetest` binary from source.
 *
 * The compiled binary is platform-specific, so it is NOT committed to the
 * repository. Run this once per machine after cloning:
 *
 *     php artisan jyotish:install-ephemeris
 *
 * Requires git, make and a C compiler (build-essential on Debian/Ubuntu,
 * Xcode command line tools on macOS). Everything it downloads is free
 * software under the AGPL — no keys, no accounts, no cost.
 */
class InstallEphemeris extends Command
{
    protected $signature = 'jyotish:install-ephemeris {--force : Rebuild even if the binary already exists}';

    protected $description = 'Build the Swiss Ephemeris binary used for planetary calculations';

    private const SOURCE_REPO = 'https://github.com/aloistr/swisseph.git';

    /** Ephemeris data files covering 1800-2400 CE. */
    private const EPHE_FILES = ['seas_18.se1', 'semo_18.se1', 'sepl_18.se1'];

    public function handle(): int
    {
        $target = config('jyotish.swetest_path');
        $ephePath = config('jyotish.ephe_path');

        if (is_executable($target) && ! $this->option('force')) {
            $this->info("Swiss Ephemeris already installed at {$target}");
            $this->line('Use --force to rebuild.');

            return self::SUCCESS;
        }

        foreach (['git', 'make', 'cc'] as $tool) {
            if (! $this->commandExists($tool)) {
                $this->error("Required build tool '{$tool}' was not found.");
                $this->line('Debian/Ubuntu: sudo apt install git build-essential');
                $this->line('macOS:         xcode-select --install');

                return self::FAILURE;
            }
        }

        $work = storage_path('app/swisseph-build');

        if (is_dir($work)) {
            Process::run(['rm', '-rf', $work]);
        }

        $this->info('Cloning Swiss Ephemeris source...');
        $clone = Process::timeout(600)->run(['git', 'clone', '--depth', '1', self::SOURCE_REPO, $work]);

        if (! $clone->successful()) {
            $this->error('Clone failed: '.$clone->errorOutput());

            return self::FAILURE;
        }

        $this->info('Compiling swetest...');
        $make = Process::timeout(900)->path($work)->run(['make', 'swetest']);

        if (! $make->successful()) {
            $this->error('Build failed: '.$make->errorOutput());

            return self::FAILURE;
        }

        @mkdir(dirname($target), 0775, true);
        @mkdir($ephePath, 0775, true);

        if (! copy($work.'/swetest', $target)) {
            $this->error('Could not copy the compiled binary into place.');

            return self::FAILURE;
        }

        chmod($target, 0755);

        $copied = 0;
        foreach (self::EPHE_FILES as $file) {
            $source = $work.'/ephe/'.$file;
            if (file_exists($source) && ! file_exists($ephePath.'/'.$file)) {
                copy($source, $ephePath.'/'.$file);
                $copied++;
            }
        }

        Process::run(['rm', '-rf', $work]);

        $this->info("Installed swetest at {$target}");
        if ($copied > 0) {
            $this->info("Copied {$copied} ephemeris data files to {$ephePath}");
        }

        $this->newLine();
        $this->info('Verifying...');

        $check = Process::run([$target, '-b15.5.1990', '-ut10:30', '-p0', '-sid1', '-fPl', '-eswe', '-edir'.$ephePath]);

        if (str_contains($check->output(), 'Sun')) {
            $this->info('Swiss Ephemeris is working correctly.');

            return self::SUCCESS;
        }

        $this->error('Verification failed. Output: '.substr($check->output(), 0, 300));

        return self::FAILURE;
    }

    private function commandExists(string $command): bool
    {
        return Process::run(['which', $command])->successful();
    }
}
