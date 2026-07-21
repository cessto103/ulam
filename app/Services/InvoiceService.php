<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function markAsPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            // Lock the INVOICE row -- a double-click blocks here until the
            // first commits, then sees status !== 'draft' and gets a clean
            // 422 instead of a second number.
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            abort_unless($invoice->status === 'draft', 422, 'Only a draft invoice can be marked as paid.');

            $counter = AppSetting::query()->where('key', 'invoice_number_next')->lockForUpdate()->first();
            abort_unless($counter, 500, 'Invoice numbering counter is not initialized.');
            $next = (int) $counter->value;

            $settings = AppSetting::allCached();
            $vat = Invoice::computeVat((string) $invoice->amount, $settings['biz_vat_status'] ?? null);

            // forceFill(), not fill() -- invoice_number/status/vat_*/
            // issuer_snapshot/issued_at are deliberately excluded from
            // $fillable so a draft update payload from the controller can
            // never set them, but that same guarding silently no-ops a
            // plain fill() call here too. This is the one trusted place
            // allowed to bypass it.
            $invoice->forceFill([
                'invoice_number' => $this->formatNumber($next, $settings),
                'status' => 'issued',
                'issued_at' => now(),
                'vat_status' => $vat['vat_status'],
                'net_amount' => $vat['net_amount'],
                'vat_amount' => $vat['vat_amount'],
                'issuer_snapshot' => $this->issuerSnapshot($settings),
            ]);

            // Render + store BEFORE save()/counter-increment -- if dompdf or
            // the disk write throws, it propagates out of this closure and
            // the whole transaction rolls back, including the not-yet-
            // committed counter state, so a failed render can never burn a
            // number. Path is keyed by the immutable id, not the
            // not-yet-assigned invoice_number, so a retry after a partial
            // failure overwrites the same file rather than accumulating
            // orphans.
            $path = "invoices/{$invoice->id}.pdf";
            Storage::disk('local')->put($path, $this->renderPdf($invoice));
            $invoice->pdf_path = $path;

            $invoice->save();
            AppSetting::set('invoice_number_next', (string) ($next + 1));

            return $invoice;
        });
    }

    public function void(Invoice $invoice, int $adminId, string $reason): Invoice
    {
        return DB::transaction(function () use ($invoice, $adminId, $reason) {
            $invoice = Invoice::lockForUpdate()->findOrFail($invoice->id);
            abort_unless($invoice->status === 'issued', 422, 'Only an issued invoice can be voided.');

            // forceFill()+save(), not update() -- same guarding issue as
            // markAsPaid() above.
            $invoice->forceFill([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => $adminId,
                'void_reason' => $reason,
            ])->save();

            return $invoice;
        });
    }

    public function renderPdf(Invoice $invoice): string
    {
        return Pdf::loadView('pdf.invoice', $this->pdfData($invoice))->setPaper('a4')->output();
    }

    private function pdfData(Invoice $invoice): array
    {
        // "Not yet issued" rather than an exact 'draft' match -- only an
        // 'issued'/'void' invoice has real frozen issuer_snapshot/vat_*/
        // issued_at data to render from; anything else should render the
        // live/draft view, which is also the safe default if status is ever
        // read before a DB round-trip (e.g. immediately after create()).
        $isDraft = ! in_array($invoice->status, ['issued', 'void'], true);

        if ($isDraft) {
            $settings = AppSetting::allCached();
            $issuer = $this->issuerSnapshot($settings);
            $vat = Invoice::computeVat((string) $invoice->amount, $settings['biz_vat_status'] ?? null);
        } else {
            $issuer = $invoice->issuer_snapshot ?? [];
            $vat = [
                'vat_status' => $invoice->vat_status,
                'net_amount' => $invoice->net_amount,
                'vat_amount' => $invoice->vat_amount,
            ];
        }

        return [
            'invoice' => $invoice,
            'is_draft' => $isDraft,
            'issuer' => $issuer,
            'vat' => $vat,
            'number_label' => $isDraft ? 'DRAFT — Payment Request' : $invoice->invoice_number,
            'date_label' => $isDraft ? now()->format('M j, Y') : $invoice->issued_at->format('M j, Y'),
        ];
    }

    private function formatNumber(int $next, array $settings): string
    {
        $prefix = $settings['invoice_number_prefix'] ?? '';
        $padding = max(1, (int) ($settings['invoice_number_padding'] ?? 6));

        return $prefix.str_pad((string) $next, $padding, '0', STR_PAD_LEFT);
    }

    private function issuerSnapshot(array $settings): array
    {
        return [
            'registered_name' => $settings['biz_registered_name'] ?? null,
            'trade_name' => $settings['biz_trade_name'] ?? null,
            'address' => $settings['biz_address'] ?? null,
            'tin' => $settings['biz_tin'] ?? null,
            'vat_status' => $settings['biz_vat_status'] ?? null,
            'atp_number' => $settings['biz_atp_number'] ?? null,
            'atp_date' => $settings['biz_atp_date'] ?? null,
            'atp_valid_until' => $settings['biz_atp_valid_until'] ?? null,
            'contact_email' => $settings['biz_contact_email'] ?? null,
            'contact_phone' => $settings['biz_contact_phone'] ?? null,
        ];
    }
}
