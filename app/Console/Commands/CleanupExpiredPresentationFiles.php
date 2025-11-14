<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PresentationFile;
use Illuminate\Support\Facades\Log;

class CleanupExpiredPresentationFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presentations:cleanup-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete presentation files that have expired (older than 1 month)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of expired presentation files...');

        $expiredFiles = PresentationFile::where('expires_at', '<=', now())->get();

        if ($expiredFiles->isEmpty()) {
            $this->info('No expired files found.');
            return 0;
        }

        $deletedCount = 0;
        foreach ($expiredFiles as $file) {
            try {
                $file->delete(); // This will also delete the physical file via model boot
                $deletedCount++;
                $this->line("Deleted expired file: {$file->filename} (ID: {$file->id})");
            } catch (\Exception $e) {
                $this->error("Failed to delete file {$file->id}: " . $e->getMessage());
                Log::error('Failed to delete expired presentation file', [
                    'file_id' => $file->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->info("Cleanup completed. Deleted {$deletedCount} expired file(s).");
        
        Log::info('Expired presentation files cleanup completed', [
            'deleted_count' => $deletedCount,
            'total_expired' => $expiredFiles->count()
        ]);

        return 0;
    }
}

