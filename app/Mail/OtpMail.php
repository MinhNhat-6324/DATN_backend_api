<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public $otpCode;
    public $recipientEmail; // <-- Tên biến để lưu email người nhận

    /**
     * Create a new message instance.
     */
    public function __construct($otpCode, $recipientEmail) 
    {
        $this->otpCode = $otpCode;
        $this->recipientEmail = $recipientEmail;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Mã xác thực OTP của bạn từ Cao Thang App',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.otp', // Đảm bảo view này tồn tại (ví dụ: resources/views/emails/otp.blade.php)
            with: [
                'otpCode' => $this->otpCode,
                'email' => $this->recipientEmail, // Truyền email vào view
            ],
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