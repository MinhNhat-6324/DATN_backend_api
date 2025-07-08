<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TinNhanEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $tenNguoiGui;
    public $noiDung;

    /**
     * Tạo một instance mới.
     */
    public function __construct(string $tenNguoiGui, string $noiDung)
    {
        $this->tenNguoiGui = $tenNguoiGui;
        $this->noiDung = $noiDung;
    }

    /**
     * Tiêu đề email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tin nhắn từ ' . $this->tenNguoiGui,
        );
    }

    /**
     * Giao diện nội dung email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tin_nhan', // Tên view chứa nội dung email
        );
    }

    /**
     * File đính kèm (nếu có).
     */
    public function attachments(): array
    {
        return [];
    }
}
