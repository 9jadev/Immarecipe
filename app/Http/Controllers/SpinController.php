<?php

namespace App\Http\Controllers;

use App\Models\SpinSetting;
use App\Models\SpinSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class SpinController extends Controller
{
    /**
     * Show the spin page.
     */
    public function index()
    {
        $settings = SpinSetting::getActive();

        if (! $settings->is_active) {
            return Inertia::render('Spin', [
                'isActive' => false,
                'message' => 'The spin wheel is currently unavailable. Please check back later!',
            ]);
        }

        return Inertia::render('Spin', [
            'isActive' => true,
            'discounts' => $settings->discounts,
            'settings' => [
                'maxSpinsPerEmail' => $settings->max_spins_per_email,
                'codeExpiryHours' => $settings->code_expiry_hours,
            ],
        ]);
    }

    /**
     * Handle spin submission.
     */
    public function spin(Request $request)
    {
        $settings = SpinSetting::getActive();

        if (! $settings->is_active) {
            return back()->withErrors(['message' => 'Spin wheel is currently unavailable.']);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        // Check if phone has already spun
        $existingSpins = SpinSubmission::where('phone', $validated['phone'])
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        if ($existingSpins >= $settings->max_spins_per_email) {
            return back()->withErrors([
                'phone' => 'You have already used your spins. Please try again later.',
            ]);
        }

        // Select random discount based on probability
        $discount = $settings->selectRandomDiscount();

        // Generate unique code
        $code = $this->generateDiscountCode($discount['percentage']);

        // Create submission
        $submission = SpinSubmission::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'discount_won' => $discount['percentage'],
            'discount_code' => $code,
            'is_claimed' => false,
            'expires_at' => now()->addHours($settings->code_expiry_hours),
        ]);

        return back()->with([
            'result' => [
                'discount' => $discount,
                'code' => $code,
                'expiresAt' => $submission->expires_at->toISOString(),
            ],
        ]);
    }

    /**
     * Verify a discount code.
     */
    public function verifyCode(string $code)
    {
        $submission = SpinSubmission::where('discount_code', $code)->first();

        if (! $submission) {
            return response()->json(['valid' => false, 'message' => 'Invalid code.']);
        }

        if ($submission->isExpired()) {
            return response()->json(['valid' => false, 'message' => 'Code has expired.']);
        }

        if ($submission->is_claimed) {
            return response()->json(['valid' => false, 'message' => 'Code has already been used.']);
        }

        return response()->json([
            'valid' => true,
            'discount' => $submission->discount_won,
            'email' => $submission->email,
        ]);
    }

    /**
     * Generate a unique discount code.
     */
    private function generateDiscountCode(int $percentage): string
    {
        $prefix = $percentage === 0 ? 'TRYAGAIN' : ($percentage === 100 ? 'FREESHIP' : 'SAVE' . $percentage);
        $suffix = strtoupper(Str::random(6));

        return $prefix . '-' . $suffix;
    }
}
