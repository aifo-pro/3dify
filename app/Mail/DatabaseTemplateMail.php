<?php

namespace App\Mail;

use App\Models\User;
use App\Services\EmailTemplateRenderer;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DatabaseTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $templateKey,
        public User $recipient,
        public array $data = [],
    ) {}

    public function build()
    {
        $locale = $this->recipient->locale ?: 'uk';
        $rendered = app(EmailTemplateRenderer::class)->render($this->templateKey, $this->data, $locale);

        $html = view('emails.templated', ['body' => $rendered['body']])->render();

        return $this->subject($rendered['subject'])->html($html);
    }
}
