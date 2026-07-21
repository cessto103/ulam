<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use Illuminate\Http\Request;

// Registered business identity + BIR receipting details, entered ahead of
// time so the admin is ready the moment the business's paperwork (TIN,
// Authority to Print, etc.) actually exists. Purely a data-entry surface for
// now -- everything here is optional and starts blank, and nothing yet
// consumes these values to generate an actual invoice/receipt document.
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
        ]);

        foreach (self::KEYS as $key) {
            // Every field is optional here (unlike About's required content),
            // so an omitted/blank field must actually clear the stored value
            // rather than being skipped -- array_key_exists, not isset, so a
            // present-but-empty-string field still overwrites a prior value.
            AppSetting::set($key, array_key_exists($key, $data) ? $data[$key] : null);
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
