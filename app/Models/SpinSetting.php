<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinSetting extends Model
{
    /** @use HasFactory<\Database\Factories\SpinSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'discounts',
        'allow_no_discount',
        'max_spins_per_email',
        'code_expiry_hours',
        'is_active',
    ];

    protected $casts = [
        'discounts' => 'array',
        'allow_no_discount' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getDefaultDiscounts(): array
    {
        return [
            ['percentage' => 0, 'label' => 'Try Again', 'color' => '#374151', 'probability' => 20],
            ['percentage' => 5, 'label' => '5% OFF', 'color' => '#D1D5DB', 'probability' => 15],
            ['percentage' => 10, 'label' => '10% OFF', 'color' => '#FDE68A', 'probability' => 15],
            ['percentage' => 15, 'label' => '15% OFF', 'color' => '#FED7AA', 'probability' => 15],
            ['percentage' => 20, 'label' => '20% OFF', 'color' => '#BBF7D0', 'probability' => 15],
            ['percentage' => 25, 'label' => '25% OFF', 'color' => '#A7F3D0', 'probability' => 10],
            ['percentage' => 50, 'label' => '50% OFF', 'color' => '#FBCFE8', 'probability' => 8],
            ['percentage' => 100, 'label' => 'FREE SHIP', 'color' => '#1F2937', 'probability' => 2],
        ];
    }

    public static function getActive(): self
    {
        return self::firstOrCreate(
            [],
            [
                'discounts' => self::getDefaultDiscounts(),
                'allow_no_discount' => true,
                'max_spins_per_email' => 1,
                'code_expiry_hours' => 24,
                'is_active' => true,
            ]
        );
    }

    public function selectRandomDiscount(): array
    {
        $totalProbability = array_sum(array_column($this->discounts, 'probability'));
        $random = mt_rand(1, $totalProbability);
        $current = 0;

        foreach ($this->discounts as $discount) {
            $current += $discount['probability'];
            if ($random <= $current) {
                return $discount;
            }
        }

        return $this->discounts[0];
    }
}
