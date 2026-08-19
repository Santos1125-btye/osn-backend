<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use App\Models\MessageAttachment;

class MessageService
{
    /**
     * Send message
     */
    public static function send(
        Conversation $conversation,
        int $senderId,
        string $type,
        ?string $message = null,
        ?int $replyTo = null,
        array $attachments = []
    ): Message {

        $start = microtime(true);

        // ------------------------------------------------------------
        // CREATE MESSAGE
        // ------------------------------------------------------------

        $step = microtime(true);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
            'message_type' => $type,
            'message' => $message,
            'reply_to_message_id' => $replyTo,
        ]);

        \Log::info('CHAT TIMING - Message::create', [
            'ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        // ------------------------------------------------------------
        // ATTACHMENTS
        // ------------------------------------------------------------

        $step = microtime(true);

        foreach ($attachments as $file) {

            $path = $file->store(
                'chat',
                'public'
            );

            MessageAttachment::create([
                'message_id' => $message->id,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        \Log::info('CHAT TIMING - Attachments', [
            'ms' => round((microtime(true) - $step) * 1000, 2),
            'count' => count($attachments),
        ]);

        // ------------------------------------------------------------
        // UPDATE CONVERSATION
        // ------------------------------------------------------------

        $step = microtime(true);

        $conversation->update([
            'last_message_at' => Carbon::now(),
        ]);

        \Log::info('CHAT TIMING - Conversation update', [
            'ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        // ------------------------------------------------------------
        // NOTIFICATION
        // ------------------------------------------------------------

        $step = microtime(true);

        if ($type !== 'system') {

            NotificationService::sendChatNotification(
                $conversation,
                User::findOrFail($senderId),
                $message
            );
        }

        \Log::info('CHAT TIMING - Notification', [
            'ms' => round((microtime(true) - $step) * 1000, 2),
        ]);

        // ------------------------------------------------------------
        // TOTAL SERVER TIME
        // ------------------------------------------------------------

        \Log::info('CHAT TIMING - TOTAL', [
            'ms' => round((microtime(true) - $start) * 1000, 2),
            'conversation_id' => $conversation->id,
            'sender_id' => $senderId,
        ]);

        return $message;
    }

    /**
     * Send system message
     */
    public static function system(
        Conversation $conversation,
        string $message
    ): Message {

        return self::send(

            $conversation,

            $conversation->customer_id,

            'system',

            $message

        );
    }
}