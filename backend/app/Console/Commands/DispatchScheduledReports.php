<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Reporting\Application\DispatchDueScheduledReports\DispatchDueScheduledReports;
use Illuminate\Console\Command;

final class DispatchScheduledReports extends Command
{
    protected $signature = 'reports:dispatch-scheduled';
    protected $description = 'Dispatch all due scheduled reports';

    public function __construct(
        private DispatchDueScheduledReports $useCase,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = new \DateTimeImmutable('now');
        $sent = ($this->useCase)($now);

        $this->info("Dispatched {$sent} scheduled report(s).");

        return self::SUCCESS;
    }
}
