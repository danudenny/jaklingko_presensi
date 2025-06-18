<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ScheduleSummaryMaterializedService;

class RefreshScheduleSummaryMaterialized extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:refresh-materialized-summary {--force : Force refresh even if data is fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh the materialized schedule summary data for fast exports';

    protected $materializedService;

    public function __construct(ScheduleSummaryMaterializedService $materializedService)
    {
        parent::__construct();
        $this->materializedService = $materializedService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting materialized schedule summary refresh...');
        
        // Check if data is fresh unless forced
        if (!$this->option('force') && $this->materializedService->isMaterializedDataFresh()) {
            $stats = $this->materializedService->getDataStats();
            $this->info("✅ Materialized data is already fresh (last updated: {$stats['last_updated']})");
            $this->info("💡 Use --force flag to refresh anyway");
            return Command::SUCCESS;
        }
        
        $this->newLine();
        $progressBar = $this->output->createProgressBar(100);
        $progressBar->setFormat('verbose');
        $progressBar->start();
        
        // Show current stats before refresh
        $statsBefore = $this->materializedService->getDataStats();
        $this->newLine();
        $this->info("📊 Current data stats:");
        $this->info("   Records: {$statsBefore['total_records']}");
        $this->info("   Date range: {$statsBefore['earliest_date']} to {$statsBefore['latest_date']}");
        $this->info("   Last updated: {$statsBefore['last_updated']}");
        $this->newLine();
        
        // Refresh the data
        try {
            $result = $this->materializedService->refreshMaterializedData();
            
            $progressBar->finish();
            $this->newLine(2);
            
            $this->info("✅ {$result['message']}");
            
            // Show updated stats
            $statsAfter = $this->materializedService->getDataStats();
            $this->newLine();
            $this->info("📊 Updated data stats:");
            $this->info("   Records: {$statsAfter['total_records']}");
            $this->info("   Date range: {$statsAfter['earliest_date']} to {$statsAfter['latest_date']}");
            $this->info("   Execution time: {$result['execution_time_ms']}ms");
            $this->newLine();
            $this->info("🚀 Schedule summary exports will now be lightning fast!");
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine(2);
            $this->error("❌ Failed to refresh materialized data: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
