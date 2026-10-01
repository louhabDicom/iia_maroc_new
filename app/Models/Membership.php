<?php

namespace App\Models;

use App\Enums\MembershipStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An IIA Maroc membership application.
 *
 * The 2024 build read `iia_adhesion` from two places to decide pricing, and the
 * second read discarded its result. This is a real entity with a lifecycle so
 * there is a single source of truth for "is this person a member".
 */
class Membership extends Model
{
    /** @use HasFactory<\Database\Factories\MembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'reference', 'civility', 'first_name', 'last_name',
        'job_title', 'organisation', 'birth_date', 'address',
        'professional_email', 'landline', 'mobile',
        'status', 'amount_paid', 'currency', 'order_id', 'activated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => MembershipStatus::class,
            'birth_date' => 'date',
            'amount_paid' => 'integer',
            'activated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @param  Builder<Membership>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', MembershipStatus::Active);
    }

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function activate(): void
    {
        $this->forceFill([
            'status' => MembershipStatus::Active,
            'activated_at' => now(),
        ])->save();
    }
}
