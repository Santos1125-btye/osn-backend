<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    protected $fillable = [

        'conversation_id',

        'sender_id',

        'message_type',

        'message',

        'reply_to_message_id',

        'edited_at',

    ];

    protected $casts = [

        'edited_at' => 'datetime',

    ];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo()
    {
        return $this->belongsTo(
            Message::class,
            'reply_to_message_id'
        );
    }

    public function replies()
    {
        return $this->hasMany(
            Message::class,
            'reply_to_message_id'
        );
    }

    public function attachments()
    {
        return $this->hasMany(
            MessageAttachment::class
        );
    }

    public function deletions()
    {
        return $this->hasMany(MessageDelete::class);
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class);
    }

    
}