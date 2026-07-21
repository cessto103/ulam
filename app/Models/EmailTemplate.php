<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;

class EmailTemplate extends Model
{
    protected $fillable = ['slug', 'subject', 'intro_md', 'note_md', 'cta_label'];

    /**
     * Renders this template for sending: substitutes {{name}}/{{code}}/etc.
     * into the subject and markdown fields, then converts the markdown to
     * HTML. The OTP code box itself is NOT part of this -- it's a fixed
     * element in the Blade shell, so an admin can never accidentally edit
     * it away.
     */
    public function render(array $vars): array
    {
        $substitute = fn (?string $text) => $text === null ? null : strtr($text, $vars);
        $converter = new CommonMarkConverter(['html_input' => 'strip']);

        return [
            'subject' => $substitute($this->subject),
            'intro_html' => (string) $converter->convert($substitute($this->intro_md) ?? ''),
            'note_html' => $this->note_md ? (string) $converter->convert($substitute($this->note_md)) : null,
            'cta_label' => $substitute($this->cta_label),
        ];
    }
}
