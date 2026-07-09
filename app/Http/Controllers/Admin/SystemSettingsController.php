<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SystemSettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::latest()->paginate(10);
        return view('admin.settings.index', compact('settings'));
    }

    public function edit($id)
    {
        $setting = Setting::findOrFail($id);
        return view('admin.settings.edit', compact('setting'));
    }

    public function update(Request $request, $id)
    {
        $request->validate(['value' => 'nullable|string']);
        $setting = Setting::findOrFail($id);
        $old = $setting->toArray();
        $setting->value = $request->value;
        $setting->save();

        // minimal audit log if ActivityLog model exists
        if (class_exists('\App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'update_setting',
                'model' => 'Setting',
                'model_id' => $setting->id,
                'description' => "Updated setting: {$setting->key}",
                'old_values' => $old,
                'new_values' => $setting->toArray(),
            ]);
        }

        return redirect()->route('admin.settings.index')->with('success', 'Setting saved.');
    }
}
