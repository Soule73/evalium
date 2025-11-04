<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class LocaleController extends Controller
{
    /**
     * Update the user's locale preference.
     */
    public function update(Request $request)
    {
        $request->validate([
            'locale' => 'required|in:fr,en'
        ]);

        $locale = $request->input('locale');

        // Store locale in session
        Session::put('locale', $locale);

        // If user is authenticated, we could also store it in the database
        if ($request->user()) {
            $request->user()->update([
                'locale' => $locale
            ]);
        }

        return back()->with('success', __('messages.locale_updated'));
    }
}
