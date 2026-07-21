<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invoice $invoice, public ?string $draftPdfBytes = null)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invoice->status === 'draft'
                ? 'Payment Request from uLam'
                : "Invoice {$this->invoice->invoice_number} from uLam",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice',
        );
    }

    public function attachments(): array
    {
        if ($this->invoice->status === 'draft') {
            return [
                Attachment::fromData(fn () => $this->draftPdfBytes, 'draft-invoice-'.$this->invoice->id.'.pdf')
                    ->withMime('application/pdf'),
            ];
        }

        return [
            Attachment::fromStorageDisk('local', $this->invoice->pdf_path)
                ->as($this->invoice->invoice_number.'.pdf'),
        ];
    }
}
