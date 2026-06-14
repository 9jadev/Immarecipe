<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        return Inertia::render('admin/Login');
    }

    /**
     * Handle an admin login request.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('admin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    /**
     * Log the admin out.
     */
    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Show the admin dashboard.
     */
    public function dashboard()
    {
        return Inertia::render('admin/Dashboard');
    }

    /**
     * Show the discount spinner page.
     */
    public function discountSpinner()
    {
        $settings = \App\Models\SpinSetting::getActive();
        $submissions = \App\Models\SpinSubmission::latest()->take(50)->get();

        $totalSpins = \App\Models\SpinSubmission::count();
        $totalDiscounts = \App\Models\SpinSubmission::where('discount_won', '>', 0)->count();
        $claimedCount = \App\Models\SpinSubmission::where('is_claimed', true)->count();
        $conversionRate = $totalDiscounts > 0 ? round(($claimedCount / $totalDiscounts) * 100, 1) : 0;

        return Inertia::render('admin/DiscountSpinner', [
            'settings' => $settings,
            'submissions' => $submissions,
            'stats' => [
                'total_spins' => $totalSpins,
                'total_discounts_given' => $totalDiscounts,
                'claimed_count' => $claimedCount,
                'conversion_rate' => $conversionRate,
            ],
        ]);
    }

    /**
     * Update spin settings.
     */
    public function updateSpinSettings(Request $request)
    {
        $validated = $request->validate([
            'discounts' => ['required', 'array'],
            'discounts.*.percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'discounts.*.label' => ['required', 'string'],
            'discounts.*.color' => ['required', 'string'],
            'discounts.*.probability' => ['required', 'integer', 'min:0', 'max:100'],
            'allow_no_discount' => ['boolean'],
            'max_spins_per_email' => ['required', 'integer', 'min:1', 'max:10'],
            'code_expiry_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'is_active' => ['boolean'],
        ]);

        $settings = \App\Models\SpinSetting::getActive();
        $settings->update($validated);

        return back()->with('success', 'Settings updated successfully.');
    }
}
