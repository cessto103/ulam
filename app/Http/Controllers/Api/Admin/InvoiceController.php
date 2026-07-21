<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoices)
    {
    }

    public function index(Request $request)
    {
        $query = Invoice::query()->with('sponsoredAd:id,product_name,company_name');

        if ($request->filled('search')) {
            $s = $request->string('search');
            $query->where(function ($q) use ($s) {
                $q->where('buyer_name', 'like', "%{$s}%")
                  ->orWhere('invoice_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $invoices = $query->orderByDesc('created_at')->paginate($request->integer('per_page', 15));

        return response()->json($invoices);
    }

    public function show(int $id)
    {
        $invoice = Invoice::with(['sponsoredAd:id,product_name,company_name', 'voidedByUser:id,name'])->findOrFail($id);

        return response()->json(['invoice' => $invoice]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $validated['created_by'] = $request->user()->id;

        $invoice = Invoice::create($validated);

        // create()'s return value doesn't reflect DB-applied column defaults
        // (status) unless re-fetched -- matters here since the frontend
        // renders a status badge straight from this response.
        return response()->json(['invoice' => $invoice->fresh()], 201);
    }

    public function update(Request $request, int $id)
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->status === 'draft', 422, 'Only a draft invoice can be edited.');

        $validated = $request->validate($this->rules(sometimes: true));
        $invoice->update($validated);

        return response()->json(['invoice' => $invoice->fresh()]);
    }

    public function destroy(int $id)
    {
        $invoice = Invoice::findOrFail($id);
        abort_unless($invoice->status === 'draft', 422, 'Only a draft invoice can be deleted -- void an issued one instead.');

        $invoice->delete();

        return response()->json(['message' => 'Draft invoice deleted.']);
    }

    public function markAsPaid(int $id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice = $this->invoices->markAsPaid($invoice);

        return response()->json(['invoice' => $invoice]);
    }

    public function void(Request $request, int $id)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:300']]);

        $invoice = Invoice::findOrFail($id);
        $invoice = $this->invoices->void($invoice, $request->user()->id, $validated['reason']);

        return response()->json(['invoice' => $invoice]);
    }

    /** Draft: rendered fresh, watermarked, never stored. Issued/void: the exact file generated at issuance, never re-rendered. */
    public function pdf(int $id)
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->status === 'draft') {
            return response($this->invoices->renderPdf($invoice), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="draft-invoice-'.$invoice->id.'.pdf"',
            ]);
        }

        abort_unless($invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404, 'PDF not found.');

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }

    public function email(Request $request, int $id)
    {
        $validated = $request->validate(['to' => ['nullable', 'email', 'max:150']]);

        $invoice = Invoice::findOrFail($id);
        $to = $validated['to'] ?? $invoice->buyer_email;
        abort_unless($to, 422, 'No recipient email on file for this invoice.');

        $draftPdfBytes = $invoice->status === 'draft' ? $this->invoices->renderPdf($invoice) : null;

        // Unlike the welcome/OTP mail convention (log and swallow, still
        // 200) -- sending IS this endpoint's whole purpose, so a failure
        // must surface as a real error, not a silently-eaten success.
        try {
            Mail::to($to)->send(new InvoiceMail($invoice, $draftPdfBytes));
        } catch (\Throwable $e) {
            Log::error('Invoice email failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return response()->json(['message' => 'Could not send the email. Check mail configuration.'], 500);
        }

        return response()->json(['message' => "Invoice emailed to {$to}."]);
    }

    private function rules(bool $sometimes = false): array
    {
        $req = $sometimes ? 'sometimes' : 'required';

        return [
            'sponsored_ad_id' => ['nullable', 'integer', 'exists:sponsored_ads,id'],
            'buyer_name' => [$req, 'string', 'max:150'],
            'buyer_contact_name' => ['nullable', 'string', 'max:120'],
            'buyer_email' => ['nullable', 'email', 'max:150'],
            'buyer_address' => ['nullable', 'string', 'max:255'],
            'description' => [$req, 'string', 'max:2000'],
            'amount' => [$req, 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
