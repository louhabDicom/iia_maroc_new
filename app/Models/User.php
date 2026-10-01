<?php

namespace App\Models;

use App\Enums\Locale;
use App\Enums\MembershipStatus;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A conference account.
 *
 * Registration is OTP-gated: an account is only usable for ordering once
 * `phone_verified_at` is set. That replaces the 2024 flow, where a user could
 * register and immediately reach the checkout with no verification at all.
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name', 'first_name', 'last_name', 'email', 'phone', 'password',
        'country_id', 'city', 'organisation', 'job_title',
        'locale', 'is_admin', 'avatar_path', 'preferences',
    ];

    protected $hidden = [
        'password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_verification_sent_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'preferences' => 'array',
        ];
    }

    // --- Relations -------------------------------------------------------

    /** @return BelongsTo<Country, $this> */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<Membership, $this> */
    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /** @return HasMany<DownloadGrant, $this> */
    public function downloadGrants(): HasMany
    {
        return $this->hasMany(DownloadGrant::class);
    }

    /** @return HasMany<SpeakerSubmission, $this> */
    public function speakerSubmissions(): HasMany
    {
        return $this->hasMany(SpeakerSubmission::class);
    }

    // --- Scopes ----------------------------------------------------------

    /** @param  Builder<User>  $query */
    public function scopeAdmins(Builder $query): void
    {
        $query->where('is_admin', true);
    }

    /** @param  Builder<User>  $query */
    public function scopeVerifiedPhone(Builder $query): void
    {
        $query->whereNotNull('phone_verified_at');
    }

    // --- State ------------------------------------------------------------

    public function hasVerifiedPhone(): bool
    {
        return $this->phone_verified_at !== null;
    }

    public function markPhoneAsVerified(): void
    {
        $this->forceFill(['phone_verified_at' => now()])->save();
    }

    public function isAdmin(): bool
    {
        return $this->is_admin;
    }

    /**
     * Whether this user gets the reduced "adhérent" rate.
     *
     * This is the one place the member/standard decision is made, replacing the
     * `iia_adhesion.etat = 'active'` check that was duplicated in
     * inscription.php and process_form.php in the 2024 build.
     */
    public function isActiveMember(): bool
    {
        return $this->memberships()
            ->where('status', MembershipStatus::Active)
            ->exists();
    }

    // --- Presentation helpers ---------------------------------------------

    public function localeEnum(): Locale
    {
        return Locale::fromRequest($this->locale);
    }

    public function displayName(): string
    {
        $full = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));

        return $full !== '' ? $full : $this->name;
    }

    public function maskedPhone(): string
    {
        $phone = (string) $this->phone;

        if (mb_strlen($phone) <= 4) {
            return $phone;
        }

        return str_repeat('•', mb_strlen($phone) - 4).mb_substr($phone, -4);
    }

    public function canAccessPresentations(): bool
    {
        return $this->downloadGrants()
            ->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    /**
     * Accounts that may place an order. Both conditions are required: a
     * verified phone and an accepted terms flag.
     */
    public function canRegister(): bool
    {
        return $this->hasVerifiedPhone() && $this->terms_accepted_at !== null;
    }

    public function acceptTerms(): void
    {
        $this->forceFill(['terms_accepted_at' => now()])->save();
    }

    /**
     * Whether the account is complete enough to be useful.
     *
     * Used by the cleanup command to find registrations abandoned between
     * "account created" and "number verified", which is the state the 2024 build
     * accumulated silently.
     */
    public function isRegistrationIncomplete(): bool
    {
        return ! $this->hasVerifiedPhone();
    }
}
