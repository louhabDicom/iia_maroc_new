<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Locale;
use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryException;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Country;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Security\SecurityEventLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Account creation, gated by phone verification.
 *
 * The shape of the flow is the important part:
 *
 *   1. POST /register  -> the account is created and the visitor is signed in
 *                         immediately, but `phone_verified_at` stays null.
 *   2. A code goes out to the number on the account.
 *   3. POST /verify-phone/verify -> the number is marked verified.
 *
 * Signing the person in at step 1 rather than after step 3 is deliberate: a
 * half-finished registration should be resumable, and forcing them to re-enter
 * the form after a failed code would be a support burden for no security gain,
 * because {@see \App\Http\Middleware\EnsurePhoneIsVerified} already blocks
 * ordering until the number is proven.
 *
 * What is *not* deliberate is reading the phone number for the challenge from
 * the request. It comes from the stored account, so the verify and resend
 * endpoints cannot be pointed at a third party's number.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly SecurityEventLogger $events,
    ) {}

    public function create(Request $request): View
    {
        return view('auth.register', [
            'countries' => Country::query()
                ->where('is_active', true)
                ->orderBy('name_'.app()->getLocale())
                ->get(),
            'locale' => Locale::parse(app()->getLocale()),
        ]);
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->accountAttributes());

            // Consent is recorded at the moment the box was ticked, not when the
            // code was verified, so the audit trail shows when the person agreed.
            $user->forceFill(['terms_accepted_at' => now()])->save();

            return $user;
        });

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        $this->events->log('auth.registered', [
            'user_id' => $user->getKey(),
            'ip' => $request->ip(),
            'context' => [
                'locale' => $user->locale,
                'country_id' => $user->country_id,
            ],
        ]);

        // A delivery failure must not leave the visitor staring at an error with
        // no way forward: the account exists, so send them to the verify page,
        // which offers a resend.
        try {
            $this->otp->issue(
                OtpPurpose::Registration,
                $user->phone,
                $user->locale,
                $request->ip(),
            );
        } catch (OtpDeliveryException $e) {
            return redirect()
                ->route('verification.notice')
                ->with('otp_unavailable', true);
        }

        return redirect()->route('verification.notice');
    }
}
