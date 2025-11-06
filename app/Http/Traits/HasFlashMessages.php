<?php

namespace App\Http\Traits;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

/**
 * Trait HasFlashMessages
 *
 * Provides methods to handle flash messages in the session.
 * Useful for displaying temporary notifications to users.
 *
 * Usage:
 * - Include this trait in controllers or classes that need to set or retrieve flash messages.
 */
trait HasFlashMessages
{
    /**
     * Flash a success message to the session and redirect the user.
     *
     * @param  string  $message  The success message to be flashed.
     * @return \Illuminate\Http\RedirectResponse Redirect response after flashing the message.
     */
    protected function flashSuccess(string $message): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];

        return back()->with('success', $flash);
    }

    /**
     * Flash an error message to the session and redirect the user.
     *
     * @param  string  $message  The error message to flash.
     * @return \Illuminate\Http\RedirectResponse Redirect response after flashing the message.
     */
    protected function flashError(string $message): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];

        return back()->with('error', $flash);
    }

    /**
     * Flash a warning message to the session and redirect the user.
     *
     * @param  string  $message  The warning message to be flashed.
     * @return \Illuminate\Http\RedirectResponse Redirect response after flashing the message.
     */
    protected function flashWarning(string $message): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];

        return back()->with('warning', $flash);
    }

    /**
     * Flash an informational message to the session and redirect the user.
     *
     * @param  string  $message  The informational message to flash.
     */
    protected function flashInfo(string $message): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];

        return back()->with('info', $flash);
    }

    /**
     * Flash a message to the session and redirect the user.
     *
     * @param  string  $message  The message to be flashed to the session.
     * @return \Illuminate\Http\RedirectResponse Redirect response after flashing the message.
     */
    protected function flashMessage(string $message): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];

        return back()->with('message', $flash);
    }

    /**
     * Redirects to the specified route with a success flash message.
     *
     * @param  string|null  $route  The name of the route to redirect to. If null, redirects to the previous page.
     * @param  string  $message  The success message to flash to the session.
     * @param  array  $parameters  Optional route parameters.
     * @param  array  $queryParams  Optional query parameters to append to the URL.
     */
    protected function redirectWithSuccess(?string $route, string $message, array $parameters = [], array $queryParams = []): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];
        if ($route === null) {
            return back()->with('success', $flash);
        }

        $redirect = redirect()->route($route, $parameters);

        if (! empty($queryParams)) {
            $redirect = $redirect->withInput($queryParams);
        }

        return $redirect->with('success', $flash);
    }

    /**
     * Redirects to a specified route with an error flash message.
     *
     * @param  string|null  $route  The name of the route to redirect to. If null, redirects back.
     * @param  string  $message  The error message to flash to the session.
     * @param  array  $parameters  Optional route parameters for the redirect.
     * @param  array  $queryParams  Optional query parameters to append to the redirect URL.
     * @return \Illuminate\Http\RedirectResponse The redirect response with the error message.
     */
    protected function redirectWithError(?string $route, string $message, array $parameters = [], array $queryParams = []): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];
        if ($route === null) {
            return back()->with('error', $flash);
        }

        $redirect = redirect()->route($route, $parameters);

        if (! empty($queryParams)) {
            $redirect = $redirect->withInput($queryParams);
        }

        return $redirect->with('error', $flash);
    }

    /**
     * Redirects to the specified route with a warning flash message.
     *
     * @param  string|null  $route  The name of the route to redirect to. If null, redirects to the previous page.
     * @param  string  $message  The warning message to flash to the session.
     * @param  array  $parameters  Optional route parameters.
     * @param  array  $queryParams  Optional query parameters to append to the URL.
     */
    protected function redirectWithWarning(?string $route, string $message, array $parameters = [], array $queryParams = []): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];
        if ($route === null) {
            return back()->with('warning', $flash);
        }

        $redirect = redirect()->route($route, $parameters);

        if (! empty($queryParams)) {
            $redirect = $redirect->withInput($queryParams);
        }

        return $redirect->with('warning', $flash);
    }

    /**
     * Redirects to a specified route with an informational flash message.
     *
     * @param  string|null  $route  The name of the route to redirect to. If null, redirects to the previous page.
     * @param  string  $message  The informational message to flash to the session.
     * @param  array  $parameters  Optional route parameters for the redirect.
     * @param  array  $queryParams  Optional query parameters to append to the redirect URL.
     * @return \Illuminate\Http\RedirectResponse The redirect response with the flashed message.
     */
    protected function redirectWithInfo(?string $route, string $message, array $parameters = [], array $queryParams = []): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];
        if ($route === null) {
            return back()->with('info', $flash);
        }

        $redirect = redirect()->route($route, $parameters);

        if (! empty($queryParams)) {
            $redirect = $redirect->withInput($queryParams);
        }

        return $redirect->with('info', $flash);
    }

    /**
     * Redirects to a specified route with a flash message.
     *
     * @param  string  $type  The type of flash message (e.g., 'success', 'error').
     * @param  string  $message  The message to be flashed to the session.
     * @param  string|null  $route  The name of the route to redirect to. If null, redirects back.
     * @param  array  $parameters  Route parameters for the redirect.
     * @param  array  $queryParams  Query parameters to append to the redirect URL.
     * @return \Illuminate\Http\RedirectResponse The redirect response with the flash message.
     */
    protected function redirectWithFlash(string $type, string $message, ?string $route = null, array $parameters = [], array $queryParams = []): RedirectResponse
    {
        $flash = [
            'id' => (string) Str::uuid(),
            'message' => $message,
        ];
        if ($route === null) {
            return back()->with($type, $flash);
        }

        $redirect = redirect()->route($route, $parameters);

        if (! empty($queryParams)) {
            $redirect = $redirect->withInput($queryParams);
        }

        return $redirect->with($type, $flash);
    }
}
