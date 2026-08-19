<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OSNAuthMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $otp,
        public readonly string $purpose,
    ) {
        $this->onQueue('emails');
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: match ($this->purpose) {
                'registration' => 'Verify Your OSN Account',
                'forgot_password' => 'OSN Password Reset Code',
                'resend_otp' => 'Your New OSN Verification Code',
                'change_email' => 'Verify Your New OSN Email',
                default => 'Your OSN Verification Code',
            },
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.osn-auth',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}