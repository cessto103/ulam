<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('subject');
            $table->text('intro_md');
            $table->text('note_md')->nullable();
            $table->string('cta_label')->nullable();
            $table->timestamps();
        });

        // Seed with the current hardcoded copy so nothing changes for anyone
        // until the admin actually edits a template. {{name}}/{{code}} are
        // substituted at send time; the OTP code box itself is a fixed
        // element in the Blade shell, never part of editable content.
        $now = now();
        DB::table('email_templates')->insert([
            [
                'slug' => 'welcome',
                'subject' => 'Maligayang pagdating sa uLam! 🍚',
                'intro_md' => <<<'MD'
                    Maligayang pagdating sa **uLam** — ang pinaka-budget-friendly na meal planner para sa mga Pilipino. Masaya kaming kasama ka na!

                    - 🤖 **AI Meal Planning** — May 3 libreng AI meal plan ka bawat buwan. Awtomatiko itong gagawa ng almusal, tanghalian, meryenda, at hapunan na angkop sa iyong budget.
                    - 💰 **Budget Tracker** — I-track ang iyong pang-araw-araw na gastos sa pagkain at alamin kung magkano ang natipid mo.
                    - 🏪 **Presyo ng Palengke** — Makita ang pinakabagong presyo ng mga sangkap mula sa mga tindahan malapit sa iyo.
                    - 👥 **Komunidad** — Mag-share ng mga budget recipes at tips kasama ang iyong mga kapitbahay.

                    Para masimulan, i-setup ang iyong monthly budget at hayaan ang uLam na mag-plan ng pagkain para sa iyong pamilya!
                    MD,
                'note_md' => 'Kung hindi ikaw ang nag-sign up, bale-walain mo lang ang email na ito.',
                'cta_label' => 'Simulan na ang pag-tipid',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'email_verification_otp',
                'subject' => '{{code}} is your uLam verification code',
                'intro_md' => "Welcome to uLam! Use this code to verify your email and finish setting up your account. It expires in 10 minutes.",
                'note_md' => "If you didn't create a uLam account, you can safely ignore this email.",
                'cta_label' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'password_reset_otp',
                'subject' => '{{code}} is your uLam password reset code',
                'intro_md' => 'Use this code in the uLam app to reset your password. It expires in 10 minutes.',
                'note_md' => "If you didn't ask to reset your password, you can safely ignore this email — your password stays the same.",
                'cta_label' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'secondary_email_otp',
                'subject' => '{{code}} is your uLam verification code',
                'intro_md' => 'Use this code to verify your secondary email address. It expires in 10 minutes.',
                'note_md' => "If you didn't request this, you can safely ignore this email.",
                'cta_label' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};
