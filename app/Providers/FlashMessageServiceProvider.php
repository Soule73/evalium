<?php

namespace App\Providers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class FlashMessageServiceProvider extends ServiceProvider
{
    /**
     * Register flash message macros for RedirectResponse.
     */
    public function boot(): void
    {
        RedirectResponse::macro('flashSuccess', function (string $message) {
            $flash = [
                'id' => (string) Str::uuid(),
                'message' => $message,
            ];

            return $this->with('success', $flash);
        });

        RedirectResponse::macro('flashError', function (string $message) {
            $flash = [
                'id' => (string) Str::uuid(),
                'message' => $message,
            ];

            return $this->with('error', $flash);
        });

        RedirectResponse::macro('flashWarning', function (string $message) {
            $flash = [
                'id' => (string) Str::uuid(),
                'message' => $message,
            ];

            return $this->with('warning', $flash);
        });

        RedirectResponse::macro('flashInfo', function (string $message) {
            $flash = [
                'id' => (string) Str::uuid(),
                'message' => $message,
            ];

            return $this->with('info', $flash);
        });
    }
}
