<?php

namespace App\Console\Commands;

use App\Http\Controllers\GlobalKilometerReportGeneratorController;
use App\Models\GlobalKilometerReport;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class CreateGlobalKMReportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'km:generate {year?} {month?} {period?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate global kilometer reports for a specific period';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $year = $this->argument('year') ?? Carbon::now()->year;
        $month = $this->argument('month') ?? Carbon::now()->month;
        $period = $this->argument('period') ?? 1;

        // Cast to integers
        $year = (int)$year;
        $month = (int)$month;
        $period = (int)$period;
        
        $monthName = Carbon::create($year, $month, 1)->format('F');
        
        $this->info("Generating global kilometer reports for {$monthName} {$year}, period {$period}...");
        
        // Check if there are any existing global kilometer reports for this period
        $existingCount = GlobalKilometerReport::where('year', $year)
            ->where('month', $month)
            ->where('period', $period)
            ->count();
        
        if ($existingCount > 0) {
            if (!$this->confirm("There are {$existingCount} existing reports for this period. Do you want to overwrite them?")) {
                $this->info('Operation canceled.');
                return;
            }
        }
        
        // Create a mock request with the parameters
        $request = new Request([
            'year' => $year,
            'month' => $month,
            'period' => $period,
        ]);
        
        // Create an instance of the generator controller
        $controller = new GlobalKilometerReportGeneratorController();
        
        // Call the generate method
        try {
            $controller->generate($request);
            
            $newCount = GlobalKilometerReport::where('year', $year)
                ->where('month', $month)
                ->where('period', $period)
                ->count();
                
            $this->info("Successfully generated {$newCount} global kilometer reports.");
        } catch (\Exception $e) {
            $this->error("Failed to generate global kilometer reports: {$e->getMessage()}");
        }
    }
}
