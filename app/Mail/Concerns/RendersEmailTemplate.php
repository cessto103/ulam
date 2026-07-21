<?php

namespace App\Mail\Concerns;

use App\Models\AppSetting;
use App\Models\EmailTemplate;

trait RendersEmailTemplate
{
    private function loadTemplate(string $slug, array $vars): array
    {
        $template = EmailTemplate::where('slug', $slug)->firstOrFail();
        $rendered = $template->render($vars);

        $logo = AppSetting::get('branding_logo_light');
        $rendered['logo_url'] = $logo ? rtrim(config('app.url'), '/') . $logo : null;

        return $rendered;
    }
}
