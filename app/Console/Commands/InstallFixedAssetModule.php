<?php

namespace App\Console\Commands;

use App\Services\FixedAsset\FixedAssetAccountHeadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class InstallFixedAssetModule extends Command
{
    protected $signature = 'fixed-assets:install {--migrate : Run database migrations first}';
    protected $description = 'Install/update Fixed Asset Management module accounts and base settings.';

    public function handle(FixedAssetAccountHeadService $accountHeadService): int
    {
        if ($this->option('migrate')) {
            $this->info('Running migrations...');
            Artisan::call('migrate', ['--force' => true]);
            $this->line(Artisan::output());
        }

        if ($accountHeadService->isInstalled()) {
            $this->info('Fixed Asset module is already installed. No account heads were changed.');
            return self::SUCCESS;
        }

        $this->info('Ensuring fixed asset account heads...');
        $heads = $accountHeadService->ensureRequiredHeads();

        foreach ($heads as $key => $account) {
            $this->line(sprintf('%s => %s | %s', $key, $account->account_code, $account->account_name));
        }

        $this->info('Fixed Asset module install/update completed.');
        return self::SUCCESS;
    }
}
