<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'nullable|string',
            'message_type' => 'required|in:text,image,voice,video,document',
            'reply_to_message_id' => 'nullable|exists:messages,id',
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}