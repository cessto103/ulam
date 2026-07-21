<?php

namespace App\Mail;

use App\Mail\Concerns\RendersEmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels, RendersEmailTemplate;

    private array $rendered;

    public function __construct(public User $user, public string $code)
    {
        $this->rendered = $this->loadTemplate('email_verification_otp', [
            '{{name}}' => $user->name,
            '{{code}}' => $code,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->rendered['subject'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.otp-code',
            with: [
                'introHtml' => $this->rendered['intro_html'],
                'noteHtml' => $this->rendered['note_html'],
                'logoUrl' => $this->rendered['logo_url'],
            ],
        );
    }
}
