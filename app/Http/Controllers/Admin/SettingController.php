<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct(private SettingsService $settingsService) {}

    public function index(Request $request): View
    {
        $tab = $request->get('tab', 'general');

        $settings = Setting::where('group', $tab)->pluck('value', 'key')->toArray();

        return view('admin.settings.index', compact('settings', 'tab'));
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = $request->input('tab', 'general');
        $settings = $request->except(['_token', '_method', 'tab']);

        $this->settingsService->updateMany($settings, $tab);

        return redirect()->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'Settings updated successfully.');
    }
}
