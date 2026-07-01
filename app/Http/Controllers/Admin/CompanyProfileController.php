<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CompanyProfileController extends Controller
{
    public function edit()
    {
        $profile = CompanyProfile::first();
        return view('admin.company.edit', compact('profile'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
        ]);

        $profile = CompanyProfile::first() ?? new CompanyProfile();
        $old = $profile->toArray();
        $profile->fill($request->only(['name', 'address', 'phone', 'email', 'website']));

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('public/logos');
            $profile->logo_path = Storage::url($path);
        }

        $profile->save();

        if (class_exists('\App\Models\ActivityLog')) {
            \App\Models\ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'update_company_profile',
                'model' => 'CompanyProfile',
                'model_id' => $profile->id,
                'description' => "Updated company profile",
                'old_values' => $old,
                'new_values' => $profile->toArray(),
            ]);
        }

        return redirect()->route('admin.company.edit')->with('success', 'Company profile saved.');
    }
}
