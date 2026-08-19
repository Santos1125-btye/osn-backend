<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestResendEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'OSN Email System Test',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <h1 style="color: #C8A96A;">OSN</h1>

                    <h2>Email System Test</h2>

                    <p>
                        This is a test email from the OSN Laravel backend.
                    </p>

                    <p>
                        If you received this email, the Resend integration
                        is working correctly.
                    </p>

                    <p>
                        <strong>Status:</strong> Email delivery successful ✅
                    </p>

                    <p style="margin-top: 30px; color: #777;">
                        OSN — On-demand Services Network
                    </p>
                </div>
            ',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}