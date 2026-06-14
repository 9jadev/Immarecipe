<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SpinSubmission extends Model
{
    /** @use HasFactory<\Database\Factories\SpinSubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'discount_won',
        'discount_code',
        'is_claimed',
        'claimed_at',
        'expires_at',
    ];

    protected $casts = [
        'is_claimed' => 'boolean',
        'claimed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function markAsClaimed(): void
    {
        $this->update([
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);
    }
}
