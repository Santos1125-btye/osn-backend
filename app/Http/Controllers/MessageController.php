<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Services\MessageService;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UploadMessageAttachmentRequest;
use App\Models\Message;
use App\Services\AttachmentService;
use App\Models\MessageDelete;
use App\Http\Requests\UpdateMessageRequest;

class MessageController extends Controller
{
    /**
     * Conversation messages
     */
    public function index(Conversation $conversation)
    {
        $user = Auth::user();

        $providerId = optional($user->provider)->id;

        abort_unless(

            $conversation->customer_id == $user->id ||

            $conversation->provider_id == $providerId,

            403

        );

        return response()->json(

            $conversation
                ->messages()
                ->with([
                    'sender:id,first_name,last_name,email,phone',
                    'replyTo',
                    'attachments',
                ])
                ->oldest()
                ->paginate(30)

        );
    }

    /**
     * Send message
     */
    public function store(
        SendMessageRequest $request
    ) {

        $conversation = Conversation::findOrFail(
            $request->conversation_id
        );

        $user = Auth::user();

        $providerId = optional($user->provider)->id;

        abort_unless(

            $conversation->customer_id == $user->id ||

            $conversation->provider_id == $providerId,

            403

        );

        if (
            $conversation->status !== 'active'
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Conversation has been closed.'
            ], 403);
        }

        $message = MessageService::send(
            $conversation,
            $user->id,
            $request->message_type,
            $request->message,
            $request->reply_to_message_id,
            $request->file('attachments', [])
        );

        return response()->json([

            'success' => true,

            'message' => 'Message sent.',

            'data' => $message->load([
                'sender',
                'attachments',
                'replyTo',
            ])

        ], 201);

    }

    public function upload(
        UploadMessageAttachmentRequest $request
    ) {
        $message = Message::with('conversation')
            ->findOrFail($request->message_id);

        $user = Auth::user();

        $providerId = optional($user->provider)->id;

        abort_unless(

            $message->conversation->customer_id == $user->id ||

            $message->conversation->provider_id == $providerId,

            403

        );

        $attachment = AttachmentService::upload(
            $message,
            $request->file('file')
        );

        return response()->json([

            'success' => true,

            'data' => $attachment,

        ], 201);
    }

    public function destroy(Message $message)
    {
        abort_unless(
            $message->sender_id == Auth::id(),
            403
        );

        abort_if(
            $message->message_type === 'system',
            403,
            'System messages cannot be deleted.'
        );

        MessageDelete::firstOrCreate(
            [
                'message_id' => $message->id,
                'user_id' => Auth::id(),
            ],
            [
                'deleted_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Message removed.'
        ]);
    }

    // 👇 Add it here
    public function update(
        UpdateMessageRequest $request,
        Message $message
    ) {
        abort_unless(
            $message->sender_id == Auth::id(),
            403
        );

        // Prevent editing system messages
        abort_if(
            $message->message_type === 'system',
            403,
            'System messages cannot be edited.'
        );

        // Prevent editing if the conversation is closed
        abort_if(
            $message->conversation->status !== 'active',
            403,
            'Conversation is closed.'
        );

        $message->update([
            'message' => $request->message,
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully.',
            'data' => $message->fresh(),
        ]);
    }
}