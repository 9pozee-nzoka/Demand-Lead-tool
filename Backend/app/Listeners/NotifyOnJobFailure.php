<?php

namespace App\Listeners;

use App\Models\User;
use App\Notifications\JobFailedNotification;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class NotifyOnJobFailure
{
    public function handle(JobFailed $event): void
    {
        // Log the failure
        Log::error('Job failed', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job' => $event->job->getName(),
            'exception' => $event->exception->getMessage(),
            'trace' => $event->exception->getTraceAsString(),
        ]);

        // Extract job name
        $jobName = $event->job->getName();
        $exception = $event->exception->getMessage();

        // Get data from payload
        $payload = json_decode($event->job->getRawBody(), true);
        $data = $payload['data'] ?? [];

        // Notify super admins
        $admins = User::where('is_super_admin', true)
            ->where('status', 'active')
            ->get();

        foreach ($admins as $admin) {
            try {
                $admin->notify(new JobFailedNotification($jobName, $exception, $data));
            } catch (\Exception $e) {
                Log::error('Failed to send job failure notification', [
                    'admin_id' => $admin->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
