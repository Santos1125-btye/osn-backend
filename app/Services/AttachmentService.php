<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Validation\ValidationException;

class AttachmentService
{
    public static function upload(
        Message $message,
        $file
    ): MessageAttachment {

        $allowed = [

            'image' => [
                'image/jpeg',
                'image/png',
                'image/webp',
            ],

            'voice' => [
                'audio/mpeg',
                'audio/wav',
                'audio/x-wav',
                'audio/mp4',
                'audio/aac',
                'audio/ogg',
            ],

            'video' => [
                'video/mp4',
                'video/quicktime',
                'video/x-msvideo',
                'video/x-matroska',
            ],

            'document' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],

        ];

        if (
            isset($allowed[$message->message_type]) &&
            !in_array(
                $file->getMimeType(),
                $allowed[$message->message_type]
            )
        ) {
            throw ValidationException::withMessages([
                'file' => [
                    'The uploaded file does not match the message type.'
                ]
            ]);
        }

        $path = $file->store(
            'chat',
            'public'
        );

        return MessageAttachment::create([

            'message_id' => $message->id,

            'file_name' => $file->getClientOriginalName(),

            'file_path' => $path,

            'mime_type' => $file->getMimeType(),

            'file_size' => $file->getSize(),

        ]);
    }
}