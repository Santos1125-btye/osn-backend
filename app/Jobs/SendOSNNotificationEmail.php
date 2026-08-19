<?php

namespace App\Jobs;

use App\Mail\OSNNotificationMail;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Throwable;

class SendOSNNotificationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Maximum number of attempts.
     */
    public int $tries = 3;

    /**
     * Retry delays in seconds.
     */
    public array $backoff = [30, 120, 300];

    /**
     * Stop retrying after 15 minutes.
     */
    public function retryUntil(): \DateTimeInterface
    {
        return now()->addMinutes(15);
    }

    public function __construct(
        public readonly int $notificationId,
    ) {
        $this->onQueue('emails');
    }

    public function handle(): void
    {
        $notification = Notification::query()
            ->with('user')
            ->find($this->notificationId);

        if (!$notification) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CHAT NOTIFICATIONS
        |--------------------------------------------------------------------------
        |
        | Chat already has real-time FCM/in-app notification delivery.
        | Do not send an email for every chat message.
        |
        */

        if ($notification->type === 'chat') {
            return;
        }

        /** @var User|null $user */
        $user = $notification->user;

        if (!$user) {
            Log::warning('OSN email skipped: notification user not found.', [
                'notification_id' => $notification->id,
            ]);

            return;
        }

        $email = trim((string) $user->email);

        if (
            $email === '' ||
            !filter_var($email, FILTER_VALIDATE_EMAIL)
        ) {
            Log::warning('OSN email skipped: invalid user email.', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
            ]);

            return;
        }

        /*
         * Create the delivery record once.
         */
        $delivery = DB::table('notification_emails')
            ->where('notification_id', $notification->id)
            ->first();

        if ($delivery?->status === 'sent') {
            return;
        }

        if (!$delivery) {
            DB::table('notification_emails')->insert([
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'email' => $email,
                'status' => 'pending',
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /*
         * Count this delivery attempt.
         */
        DB::table('notification_emails')
            ->where('notification_id', $notification->id)
            ->update([
                'attempts' => DB::raw('attempts + 1'),
                'status' => 'pending',
                'last_error' => null,
                'updated_at' => now(),
            ]);

        try {
            Mail::to($email)->send(
                new OSNNotificationMail(
                    recipientName: $user->full_name ?: $user->first_name,
                    title: $notification->title,
                    body: $notification->message,
                    type: $notification->type,
                    data: $notification->data ?? [],
                )
            );

            DB::table('notification_emails')
                ->where('notification_id', $notification->id)
                ->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            Log::info('OSN notification email sent.', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'email' => $email,
                'type' => $notification->type,
            ]);
        } catch (Throwable $exception) {
            DB::table('notification_emails')
                ->where('notification_id', $notification->id)
                ->update([
                    'status' => 'failed',
                    'last_error' => mb_substr(
                        $exception->getMessage(),
                        0,
                        5000
                    ),
                    'updated_at' => now(),
                ]);

            Log::error('OSN notification email failed.', [
                'notification_id' => $notification->id,
                'user_id' => $user->id,
                'email' => $email,
                'type' => $notification->type,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        DB::table('notification_emails')
            ->where('notification_id', $this->notificationId)
            ->update([
                'status' => 'failed',
                'last_error' => mb_substr(
                    $exception->getMessage(),
                    0,
                    5000
                ),
                'updated_at' => now(),
            ]);

        Log::error('OSN notification email permanently failed.', [
            'notification_id' => $this->notificationId,
            'error' => $exception->getMessage(),
        ]);
    }
}