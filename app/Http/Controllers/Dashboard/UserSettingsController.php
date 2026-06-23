<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    public function update(Request $request)
    {
        $data = $request->validate([
            'notify_email' => ['nullable', 'boolean'],
            'notify_expiry' => ['nullable', 'boolean'],
            'notify_login' => ['nullable', 'boolean'],
            'timezone' => ['required', 'string', 'max:60'],
            'theme' => ['required', 'in:light'],
        ]);

        $user = $request->user();
        $prefs = $user->resolvedPreferences();

        $user->update([
            'preferences' => array_merge($prefs, [
                'notify_email' => $request->boolean('notify_email'),
                'notify_expiry' => $request->boolean('notify_expiry'),
                'notify_login' => $request->boolean('notify_login'),
                'timezone' => $data['timezone'],
                'theme' => $data['theme'],
            ]),
        ]);

        return back()->with('success', 'Settings saved.');
    }
}
