<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Minimal HTML body from admin-managed templates (DB). Used by auth notifications
 * so Laravel does not wrap content in notifications::email markdown layout.
 */
class RenderedTemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $body,
    ) {}

    public function build()
    {
        $html = view('emails.templated', ['body' => $this->body])->render();

        return $this->subject($this->subjectLine)->html($html);
    }
}
