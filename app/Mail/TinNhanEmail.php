<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;

class TinNhanEmail extends Mailable
{
    use Queueable, SerializesModels;

    public string $tenNguoiGui;
    public string $emailNguoiGui;
    public string $noiDung;

    /**
     * Tạo instance mới.
     */
    public function __construct(string $tenNguoiGui, string $emailNguoiGui, string $noiDung)
    {
        $this->tenNguoiGui = $tenNguoiGui;
        $this->emailNguoiGui = $emailNguoiGui;
        $this->noiDung = $noiDung;
    }

    /**
     * Tiêu đề và người gửi email.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->emailNguoiGui, $this->tenNguoiGui),
            subject: 'Tin nhắn từ ' . $this->tenNguoiGui,
        );
    }

    /**
     * Giao diện nội dung email.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.tin_nhan',
        );
    }

    /**
     * File đính kèm (không có).
     */
    public function attachments(): array
    {
        return [];
    }
}
