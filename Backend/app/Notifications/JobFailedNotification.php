<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JobFailedNotification extends Notification
{
    use Queueable;

    protected string $jobName;
    protected string $exception;
    protected array $data;

    public function __construct(string $jobName, string $exception, array $data = [])
    {
        $this->jobName = $jobName;
        $this->exception = $exception;
        $this->data = $data;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->error()
            ->subject("Job Failed: {$this->jobName}")
            ->line("A background job has failed:")
            ->line("**Job:** {$this->jobName}")
            ->line("**Error:** " . substr($this->exception, 0, 200))
            ->line("**Time:** " . now()->toDateTimeString())
            ->action('View Logs', url('/admin/jobs'))
            ->line('Please investigate this issue.');
    }

    public function toArray($notifiable): array
    {
        return [
            'job_name' => $this->jobName,
            'exception' => $this->exception,
            'data' => $this->data,
            'failed_at' => now()->toDateTimeString(),
        ];
    }
}
