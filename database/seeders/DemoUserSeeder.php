<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\MembershipStatus;
use App\Models\Country;
use App\Models\Edition;
use App\Models\Membership;
use App\Models\Room;
use App\Models\User;
use App\Services\Otp\PhoneNumber;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * A small set of working accounts for a local install.
 *
 * Everything here is a known password on a machine that must never face the
 * internet, so the seeder refuses to run in production rather than trusting the
 * operator to remember. The password is intentionally trivial and stated in the
 * README, because a demo account whose password has to be looked up is a demo
 * nobody uses.
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoUserSeeder skipped: demo credentials are not seeded in production.');

            return;
        }

        $morocco = Country::query()->where('iso2', 'MA')->first();
        $edition = Edition::current();

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@arabcia.test'],
            [
                'name' => 'Amina Bennis',
                'first_name' => 'Amina',
                'last_name' => 'Bennis',
                'phone' => PhoneNumber::normalise('0612345678'),
                'password' => Hash::make('Password!2026'),
                'country_id' => $morocco?->getKey(),
                'city' => 'Rabat',
                'organisation' => 'ARABCIA',
                'job_title' => 'Administratrice',
                'locale' => 'fr',
                'is_admin' => true,
                'phone_verified_at' => now(),
                'terms_accepted_at' => now(),
            ],
        );

        // A member and a non-member, so the pricing page and the checkout can be
        // exercised in both states without editing a membership record by hand.
        $member = User::query()->updateOrCreate(
            ['email' => 'membre@arabcia.test'],
            [
                'name' => 'Youssef El Amrani',
                'first_name' => 'Youssef',
                'last_name' => 'El Amrani',
                'phone' => PhoneNumber::normalise('0655443322'),
                'password' => Hash::make('Password!2026'),
                'country_id' => $morocco?->getKey(),
                'city' => 'Casablanca',
                'organisation' => 'Audit Conseil',
                'job_title' => 'Directeur d\'audit interne',
                'locale' => 'fr',
                'is_admin' => false,
                'phone_verified_at' => now(),
                'terms_accepted_at' => now(),
            ],
        );

        Membership::query()->updateOrCreate(
            ['user_id' => $member->getKey(), 'organisation' => 'Audit Conseil'],
            [
                'reference' => 'IIA-MA-0001',
                'status' => MembershipStatus::Active,
                'activated_at' => now()->subYears(3),
            ],
        );

        $standard = User::query()->updateOrCreate(
            ['email' => 'participant@arabcia.test'],
            [
                'name' => 'Fatima Zahra Idrissi',
                'first_name' => 'Fatima Zahra',
                'last_name' => 'Idrissi',
                'phone' => PhoneNumber::normalise('0700112233'),
                'password' => Hash::make('Password!2026'),
                'country_id' => $morocco?->getKey(),
                'city' => 'Fès',
                'organisation' => 'Groupe BNA',
                'job_title' => 'Responsable conformité',
                'locale' => 'ar',
                'is_admin' => false,
                'phone_verified_at' => now(),
                'terms_accepted_at' => now(),
            ],
        );

        // An unverified account, so the verification gate can be seen working
        // without having to walk through the flow by hand.
        User::query()->updateOrCreate(
            ['email' => 'en-attente@arabcia.test'],
            [
                'name' => 'Karim Alaoui',
                'first_name' => 'Karim',
                'last_name' => 'Alaoui',
                'phone' => PhoneNumber::normalise('0611998877'),
                'password' => Hash::make('Password!2026'),
                'country_id' => $morocco?->getKey(),
                'locale' => 'fr',
                'is_admin' => false,
                'phone_verified_at' => null,
                'terms_accepted_at' => now(),
            ],
        );

        $this->command?->info('Seeded demo accounts: admin@arabcia.test, membre@arabcia.test, participant@arabcia.test, en-attente@arabcia.test (password: Password!2026)');
    }
}
