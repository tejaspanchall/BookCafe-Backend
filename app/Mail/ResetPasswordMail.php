<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public $resetToken;
    public $user;

    public function __construct($user, $resetToken)
    {
        $this->user = $user;
        $this->resetToken = $resetToken;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '
                <h1>Hello ' . $this->user->firstname . ',</h1>
                <p>You have requested to reset your password. Here is your reset token:</p>
                <p><strong>' . $this->resetToken . '</strong></p>
                <p>If you did not request a password reset, please ignore this email.</p>
                <p>This token will expire in 60 minutes.</p>
                <p>Best regards,<br>Team BookCafe</p>
            '
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
