<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Update the user's locale preference.
     *
     * - Store locale in session
     * - If user is authenticated, we could also store it in the database
     *
     * @param  Request  $request  The HTTP request containing the locale data.
     * @return \Illuminate\Http\RedirectResponse Redirects back with a success message.
     */
    public function update(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:fr,en',
        ]);

        $locale = $request->input('locale');

        Session::put('locale', $locale);

        if ($request->user()) {
            $request->user()->update([
                'locale' => $locale,
            ]);
        }

        return back()->flashInfo(__('messages.locale_updated'));
    }
}
