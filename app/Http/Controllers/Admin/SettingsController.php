<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Settings\BulletinSettings;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin settings management for school name, locale, logo and bulletin options.
 */
class SettingsController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly GeneralSettings $generalSettings,
        private readonly BulletinSettings $bulletinSettings
    ) {}

    /**
     * Display the settings page with current general and bulletin settings.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Index', [
            'general' => [
                'school_name' => $this->generalSettings->school_name,
                'logo_path' => $this->generalSettings->logo_path,
                'logo_url' => $this->generalSettings->logo_path ? Storage::url($this->generalSettings->logo_path) : null,
                'default_locale' => $this->generalSettings->default_locale,
            ],
            'bulletin' => [
                'show_ranking' => $this->bulletinSettings->show_ranking,
                'show_class_average' => $this->bulletinSettings->show_class_average,
                'show_min_max' => $this->bulletinSettings->show_min_max,
            ],
        ]);
    }

    /**
     * Update general settings (school name, default locale).
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['required', 'string', 'max:255'],
            'default_locale' => ['required', 'string', 'in:fr,en'],
        ]);

        $this->generalSettings->school_name = $validated['school_name'];
        $this->generalSettings->default_locale = $validated['default_locale'];
        $this->generalSettings->save();

        return back()->flashSuccess(__('messages.settings_general_updated'));
    }

    /**
     * Update bulletin display settings.
     */
    public function updateBulletin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'show_ranking' => ['required', 'boolean'],
            'show_class_average' => ['required', 'boolean'],
            'show_min_max' => ['required', 'boolean'],
        ]);

        $this->bulletinSettings->show_ranking = $validated['show_ranking'];
        $this->bulletinSettings->show_class_average = $validated['show_class_average'];
        $this->bulletinSettings->show_min_max = $validated['show_min_max'];
        $this->bulletinSettings->save();

        return back()->flashSuccess(__('messages.settings_bulletin_updated'));
    }

    /**
     * Upload or replace the school logo.
     */
    public function uploadLogo(Request $request): RedirectResponse
    {
        $request->validate([
            'logo' => ['required', 'image', 'mimes:png,jpg,jpeg,svg', 'max:2048'],
        ]);

        if ($this->generalSettings->logo_path) {
            Storage::disk('public')->delete($this->generalSettings->logo_path);
        }

        $path = $request->file('logo')->store('logos', 'public');
        $this->generalSettings->logo_path = $path;
        $this->generalSettings->save();

        return back()->flashSuccess(__('messages.settings_logo_updated'));
    }

    /**
     * Remove the school logo.
     */
    public function deleteLogo(): RedirectResponse
    {
        if ($this->generalSettings->logo_path) {
            Storage::disk('public')->delete($this->generalSettings->logo_path);
            $this->generalSettings->logo_path = null;
            $this->generalSettings->save();
        }

        return back()->flashSuccess(__('messages.settings_logo_deleted'));
    }
}
