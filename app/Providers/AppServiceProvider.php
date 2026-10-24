<?php

namespace App\Providers;

use App\Notifications\Channels\WhatsappChannel;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Notification::extend('whatsapp', fn($app) => $app->make(WhatsappChannel::class));

        // Masa trial: SEMUA email notifikasi (Section/GA/Direktur/dst, apa
        // pun penerima aslinya) dialihkan ke satu alamat test, supaya tidak
        // ada staf asli yang kebagian email percobaan. Matikan dengan
        // mengosongkan MAIL_TRIAL_REDIRECT_TO di .env begitu siap live.
        if ($redirectTo = config('mail.trial_redirect_to')) {
            Mail::alwaysTo($redirectTo);
        }
    }
}
