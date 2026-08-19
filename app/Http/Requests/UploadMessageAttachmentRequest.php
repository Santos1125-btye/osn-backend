<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadMessageAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'message_id' => 'required|exists:messages,id',

            'file' => [
                'required',
                'file',
                'max:51200', // 50MB
                'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,mp3,wav,m4a,aac,ogg,mp4,mov,avi,mkv'
            ],

        ];
    }
}