<?php

namespace App\Mail;

use App\Mail\Concerns\RendersEmailTemplate;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels, RendersEmailTemplate;

    private array $rendered;

    public function __construct(public User $user)
    {
        $this->rendered = $this->loadTemplate('welcome', ['{{name}}' => $user->name]);
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
            view: 'emails.welcome',
            with: [
                'introHtml' => $this->rendered['intro_html'],
                'noteHtml' => $this->rendered['note_html'],
                'ctaLabel' => $this->rendered['cta_label'],
                'logoUrl' => $this->rendered['logo_url'],
            ],
        );
    }
}
