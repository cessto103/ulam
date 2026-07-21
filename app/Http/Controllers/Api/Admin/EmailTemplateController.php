<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Mail\EmailVerificationOtpMail;
use App\Mail\PasswordResetOtpMail;
use App\Mail\SecondaryEmailOtpMail;
use App\Mail\WelcomeMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EmailTemplateController extends Controller
{
    private const SLUGS = ['welcome', 'email_verification_otp', 'password_reset_otp', 'secondary_email_otp'];

    public function index()
    {
        return response()->json(['templates' => EmailTemplate::orderBy('slug')->get()]);
    }

    public function update(Request $request, string $slug)
    {
        abort_unless(in_array($slug, self::SLUGS, true), 404);
        $template = EmailTemplate::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'intro_md' => ['required', 'string', 'max:5000'],
            'note_md' => ['nullable', 'string', 'max:2000'],
            'cta_label' => ['nullable', 'string', 'max:60'],
        ]);

        $template->update($validated);

        return response()->json(['template' => $template->fresh()]);
    }

    /** POST /admin/email-templates/upload-image — for pasting into intro/note markdown. */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:png,jpg,jpeg,webp,gif', 'max:2048'],
        ]);

        $path = $request->file('image')->store('email-templates', 'public');

        return response()->json(['url' => '/storage/' . $path]);
    }

    /** POST /admin/email-templates/{slug}/test — send this template to the requesting admin, using placeholder sample data. */
    public function sendTest(Request $request, string $slug)
    {
        abort_unless(in_array($slug, self::SLUGS, true), 404);

        $admin = $request->user();
        $sample = new User(['name' => 'Test User', 'email' => $admin->email]);

        try {
            match ($slug) {
                'welcome' => Mail::to($admin->email)->send(new WelcomeMail($sample)),
                'email_verification_otp' => Mail::to($admin->email)->send(new EmailVerificationOtpMail($sample, '123456')),
                'password_reset_otp' => Mail::to($admin->email)->send(new PasswordResetOtpMail($sample, '123456')),
                'secondary_email_otp' => Mail::to($admin->email)->send(new SecondaryEmailOtpMail($sample, '123456')),
            };
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not send: ' . $e->getMessage()], 422);
        }

        return response()->json(['message' => "Test email sent to {$admin->email}."]);
    }
}
