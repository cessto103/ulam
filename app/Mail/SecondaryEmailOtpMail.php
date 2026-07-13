<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SecondaryEmailOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $code)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->code} is your uLam verification code",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.secondary-email-otp',
        );
    }
}
