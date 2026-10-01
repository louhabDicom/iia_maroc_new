<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * One named attendee on an order.
 *
 * An order can cover several participants ("Inscription pour un groupe"), which
 * is what the legacy `iia_participants` rows represented, but with a proper
 * foreign key and validation rather than free-text fields interpolated into
 * SQL.
 */
class Participant extends Model
{
    /** @use HasFactory<\Database\Factories\ParticipantFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id', 'full_name', 'job_title', 'email', 'phone',
        'is_member', 'badge_name', 'dietary_requirements', 'checked_in', 'checked_in_at',
    ];

    protected function casts(): array
    {
        return [
            'is_member' => 'boolean',
            'checked_in' => 'boolean',
            'checked_in_at' => 'datetime',
            'dietary_requirements' => 'array',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email', 'email');
    }

    public function markCheckedIn(): void
    {
        $this->forceFill(['checked_in' => true, 'checked_in_at' => now()])->save();
    }

    /** The name as it should appear on a badge. */
    public function badgeLabel(): string
    {
        if (filled($this->badge_name)) {
            return $this->badge_name;
        }

        return Str::of($this->full_name)
            ->squish()
            ->limit(48)
            ->toString();
    }
}
