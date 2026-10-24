<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

abstract class Controller
{
    /**
     * Kirim notifikasi (mail/WhatsApp) tanpa membuat aksi utama (submit
     * permintaan, approve, dsb.) ikut gagal kalau pengirimannya error --
     * mis. mailbox SMTP penuh ("550 Mailbox is full") pernah bikin SELURUH
     * request 500 padahal perubahan data di DB sendiri sudah sukses.
     */
    protected function safeNotify($notifiables, $notification): void
    {
        try {
            Notification::send($notifiables, $notification);
        } catch (\Throwable $e) {
            Log::error('Gagal kirim notifikasi (' . get_class($notification) . '): ' . $e->getMessage());
        }
    }
}
