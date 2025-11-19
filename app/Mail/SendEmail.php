<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $subject;
    public $body;
    public $attachments;

    /**
     * Create a new message instance.
     */
    public function __construct($subject, $body, $attachments = [])
    {
        $this->subject = $subject;
        $this->body = $body;
        $this->attachments = [];
        $tempDir = sys_get_temp_dir();
        foreach ($attachments as $attachment) {
            $originalName = $attachment->getClientOriginalName();
            $tempPath = $tempDir . '/' . uniqid() . '_' . $originalName;
            copy($attachment->getRealPath(), $tempPath);
            $this->attachments[] = [
                'file' => $tempPath,
                'as' => $originalName,
                'mime' => $attachment->getMimeType(),
                'options' => [],
            ];
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.send',
            with: ['body' => $this->body],
        );
    }
}
