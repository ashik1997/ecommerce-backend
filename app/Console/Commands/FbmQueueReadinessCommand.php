<?php

namespace App\Console\Commands;

use App\Services\FbMarketing\FbmQueueReadinessService;
use Illuminate\Console\Command;

class FbmQueueReadinessCommand extends Command
{
    protected $signature = 'fb-marketing:queue-readiness';
    protected $description = 'Verify the FB MARKETING queue and application sync infrastructure.';

    public function handle(FbmQueueReadinessService $readiness): int
    {
        $summary = $readiness->currentSummary();
        $this->render('Queue readiness', $summary['queue']);
        $this->render('Application sync readiness', $summary['application']);

        return $summary['ready'] ? self::SUCCESS : self::FAILURE;
    }

    protected function render(string $title, array $summary): void
    {
        $this->line('');
        $this->info($title . ': ' . ($summary['ready'] ? 'READY' : 'NOT READY'));
        foreach (($summary['checks'] ?? []) as $check => $ready) {
            $this->line(($ready ? '[OK] ' : '[MISSING] ') . $check);
        }
        $this->line((string) ($summary['message'] ?? ''));
    }
}
