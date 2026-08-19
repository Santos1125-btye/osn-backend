<?php

namespace App\Services;

use App\Jobs\SendOSNNotificationEmail;
use App\Models\Notification;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Create an in-app notification,
     * send an FCM push,
     * and queue an email notification.
     */
    public static function send(
        int $userId,
        string $title,
        string $message,
        string $type,
        array $data = []
    ): Notification {
        /*
        |--------------------------------------------------------------------------
        | CREATE DATABASE NOTIFICATION
        |--------------------------------------------------------------------------
        */

        $notification = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'data' => $data,
        ]);

        /*
        |--------------------------------------------------------------------------
        | PUSH NOTIFICATION DATA
        |--------------------------------------------------------------------------
        |
        | Include the database notification ID so Flutter can:
        |
        | - Mark the exact notification as read
        | - Perform deep-link navigation
        | - Identify the notification source
        |
        */

        $pushData = array_merge(
            $data,
            [
                'notification_id' => (string) $notification->id,
                'type' => $type,
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | FCM PUSH
        |--------------------------------------------------------------------------
        |
        | Push delivery must NEVER cause the underlying business operation
        | or database notification to fail.
        |
        | If FCM is unavailable, the in-app notification still exists.
        |
        */

        try {
            app(FcmService::class)->sendToUser(
                userId: $userId,
                title: $title,
                body: $message,
                data: $pushData,
            );
        } catch (\Throwable $e) {
            Log::error(
                'FCM notification delivery failed.',
                [
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | EMAIL NOTIFICATION
        |--------------------------------------------------------------------------
        |
        | Email is deliberately queued.
        |
        | This means:
        |
        | Booking/payment/chat request
        |          ↓
        | Database notification created
        |          ↓
        | FCM attempted
        |          ↓
        | Email job queued
        |          ↓
        | API request finishes
        |
        | The user does NOT have to wait for the email provider.
        |
        */

        try {
            SendOSNNotificationEmail::dispatch(
                $notification->id
            )->afterCommit();
        } catch (\Throwable $e) {
            /*
             * Email queue failure must never break
             * the underlying business operation.
             */

            Log::error(
                'OSN notification email could not be queued.',
                [
                    'notification_id' => $notification->id,
                    'user_id' => $userId,
                    'type' => $type,
                    'error' => $e->getMessage(),
                ]
            );
        }

        return $notification;
    }

    /**
     * Send chat notification to the other participant.
     */
    public static function sendChatNotification(
        Conversation $conversation,
        User $sender,
        Message $message
    ): void {
        $recipient = null;

        /*
        |--------------------------------------------------------------------------
        | SUPPORT CONVERSATION
        |--------------------------------------------------------------------------
        */

        if (
            $conversation->conversation_type === 'support'
        ) {
            /*
             * Customer → OSN Support
             */
            if (
                $conversation->customer_id !== null &&
                $sender->id === $conversation->customer_id
            ) {
                $recipient = $conversation->supportUser;
            }

            /*
             * OSN Support → Customer
             */
            elseif (
                $conversation->support_user_id !== null &&
                $sender->id === $conversation->support_user_id
            ) {
                $recipient = $conversation->customer;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | NORMAL CUSTOMER ↔ PROVIDER CONVERSATION
        |--------------------------------------------------------------------------
        */

        else {
            /*
             * Customer → Provider
             */
            if (
                $conversation->customer_id === $sender->id
            ) {
                if ($conversation->provider) {
                    $recipient =
                        $conversation
                            ->provider
                            ->user;
                }
            }

            /*
             * Provider → Customer
             */
            else {
                $recipient = $conversation->customer;
            }
        }

        /*
        |--------------------------------------------------------------------------
        | SAFETY CHECK
        |--------------------------------------------------------------------------
        */

        if (!$recipient) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | CREATE + PUSH + EMAIL NOTIFICATION
        |--------------------------------------------------------------------------
        */

        self::send(
            $recipient->id,
            'New Message',
            $message->message_type === 'text'
                ? $message->message
                : ucfirst(
                    $message->message_type
                ) . ' received',
            'chat',
            [
                'conversation_id' =>
                    (string) $conversation->id,

                'message_id' =>
                    (string) $message->id,
            ]
        );
    }
}