<?php

namespace App\Jobs;

use App\Exports\ScheduleSummaryStreamExport;
use App\Models\User;
use App\Notifications\ExportCompleted;
use App\Notifications\ExportFailed;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class ExportScheduleSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $startDate;
    protected $endDate;
    protected $routeId;
    protected $unitIds;
    protected $driverType;
    protected $filename;
    protected $originalFilename;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public $timeout = 1800; // 30 minutes

    /**
     * Create a new job instance.
     */
    public function __construct($userId, $startDate = null, $endDate = null, $routeId = null, $unitIds = null, $driverType = null, $originalFilename = null)
    {
        $this->userId = $userId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
        $this->routeId = $routeId;
        $this->unitIds = $unitIds;
        $this->driverType = $driverType;
        $this->originalFilename = $originalFilename ?: 'schedule-summary-' . date('Y-m-d-His');
        $this->filename = 'exports/' . $this->originalFilename . '.xlsx';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Starting background export job', [
                'user_id' => $this->userId,
                'filename' => $this->filename,
                'filters' => [
                    'start_date' => $this->startDate,
                    'end_date' => $this->endDate,
                    'route_id' => $this->routeId,
                    'unit_ids' => $this->unitIds,
                    'driver_type' => $this->driverType,
                ]
            ]);

            // Set optimal memory settings for background processing
            ini_set('memory_limit', '512M');
            ini_set('max_execution_time', 0); // No time limit for background jobs
            
            // Enable garbage collection
            if (function_exists('gc_enable')) {
                gc_enable();
            }

            // Create the export
            $export = new ScheduleSummaryStreamExport(
                $this->startDate,
                $this->endDate,
                $this->routeId,
                $this->unitIds,
                $this->driverType
            );

            // Store the file in storage/app/exports/
            Excel::store($export, $this->filename, 'local');

            Log::info('Export completed successfully', [
                'user_id' => $this->userId,
                'filename' => $this->filename,
                'file_size' => Storage::disk('local')->size($this->filename),
                'memory_peak' => memory_get_peak_usage(true)
            ]);

            // Notify user of completion
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new ExportCompleted($this->filename, $this->originalFilename));
            }

        } catch (\Exception $e) {
            Log::error('Export job failed', [
                'user_id' => $this->userId,
                'filename' => $this->filename,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'memory_peak' => memory_get_peak_usage(true)
            ]);

            // Notify user of failure
            $user = User::find($this->userId);
            if ($user) {
                $user->notify(new ExportFailed($this->originalFilename, $e->getMessage()));
            }

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Export job failed permanently', [
            'user_id' => $this->userId,
            'filename' => $this->filename,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Notify user of permanent failure
        $user = User::find($this->userId);
        if ($user) {
            $user->notify(new ExportFailed($this->originalFilename, 'Export gagal setelah beberapa percobaan. Silakan hubungi administrator.'));
        }
    }
}
