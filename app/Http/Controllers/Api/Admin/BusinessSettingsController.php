<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

// Registered business identity + BIR receipting details, entered ahead of
// time so the admin is ready the moment the business's paperwork (TIN,
// Authority to Print, etc.) actually exists. The biz_* fields are all
// optional and start blank; InvoiceService reads them to stamp an issued
// invoice's frozen issuer snapshot. invoice_number_prefix/padding format
// the sequential invoice numbering scheme -- the counter itself
// (invoice_number_next) is deliberately NOT in this list, internal-only,
// touched exclusively by InvoiceService.
class BusinessSettingsController extends Controller
{
    private const KEYS = [
        'biz_registered_name',
        'biz_trade_name',
        'biz_address',
        'biz_tin',
        'biz_vat_status',
        'biz_atp_number',
        'biz_atp_date',
        'biz_atp_valid_until',
        'biz_contact_email',
        'biz_contact_phone',
        'biz_notes',
        'invoice_number_prefix',
        'invoice_number_padding',
    ];

    /** GET /admin/business-settings */
    public function show()
    {
        return response()->json($this->currentValues());
    }

    /** PUT /admin/business-settings */
    public function update(Request $request)
    {
        $data = $request->validate([
            'biz_registered_name' => ['nullable', 'string', 'max:150'],
            'biz_trade_name'      => ['nullable', 'string', 'max:150'],
            'biz_address'         => ['nullable', 'string', 'max:255'],
            'biz_tin'             => ['nullable', 'string', 'max:30'],
            'biz_vat_status'      => ['nullable', 'in:vat_registered,non_vat'],
            'biz_atp_number'      => ['nullable', 'string', 'max:60'],
            'biz_atp_date'        => ['nullable', 'date'],
            'biz_atp_valid_until' => ['nullable', 'date'],
            'biz_contact_email'   => ['nullable', 'email', 'max:150'],
            'biz_contact_phone'   => ['nullable', 'string', 'max:30'],
            'biz_notes'           => ['nullable', 'string', 'max:2000'],
            'invoice_number_prefix'  => ['nullable', 'string', 'max:20'],
            'invoice_number_padding' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        foreach (self::KEYS as $key) {
            // Every field is optional here (unlike About's required content),
            // so an omitted/blank field must actually clear the stored value
            // rather than being skipped -- array_key_exists, not isset, so a
            // present-but-empty-string field still overwrites a prior value.
            // AppSetting::value is a text column, so numeric-looking fields
            // are cast to string explicitly rather than relying on PHP's
            // implicit coercion.
            $value = array_key_exists($key, $data) ? $data[$key] : null;
            AppSetting::set($key, $value === null ? null : (string) $value);
        }

        return response()->json($this->currentValues());
    }

    private function currentValues(): array
    {
        $values = [];
        foreach (self::KEYS as $key) {
            $values[$key] = AppSetting::get($key);
        }
        return $values;
    }
}
