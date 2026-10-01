<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpDeliveryException;
use App\Exceptions\OtpVerificationException;
use App\Http\Requests\Auth\VerifyPhoneRequest;
use App\Models\OtpCode;
use App\Services\Otp\OtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Phone verification for the signed-in account.
 *
 * Every method here takes the identifier from `$request->user()->phone`, never
 * from the request body. A `/verify-phone` endpoint that accepted a `phone`
 * field would be an open relay: it would let anyone ask this application to
 * spend SMS credit on a third party's number, and would let a challenge be
 * pointed at somebody else's handset to block their registration.
 */
class VerificationController extends Controller
{
    public function __construct(private readonly OtpService $otp) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('verification.done');
        }

        return view('auth.verify', [
            'user' => $user,
            // Shown back to the visitor so they can tell which number the code
            // went to. Masked, because a verification page is exactly the screen
            // somebody shoulder-surfs, and it is a public-facing form.
            'maskedPhone' => $user->maskedPhone(),
            'codeLength' => (int) config('otp.length', 6),
            'expiresInSeconds' => $this->secondsUntilExpiry($user->phone),
            'cooldownSeconds' => (int) config('otp.resend_cooldown_seconds', 60),
            'attemptsLeft' => $this->attemptsLeft($user->phone),
        ]);
    }

    public function verify(VerifyPhoneRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('verification.done');
        }

        try {
            $this->otp->verify(
                OtpPurpose::Registration,
                $user->phone,
                $request->code(),
                $request->ip(),
            );
        } catch (OtpVerificationException $e) {
            // Thrown back as a validation error rather than a flash message: the
            // message belongs next to the field, the form keeps the visitor's
            // place, and the reason stays generic so a wrong code and an expired
            // one are indistinguishable from the outside.
            throw ValidationException::withMessages([
                'code' => $e->userMessage(),
            ]);
        }

        $user->markPhoneAsVerified();

        // The session identifier is rotated at the privilege change, so a
        // session id captured before verification is worthless afterwards.
        $request->session()->regenerate();
        Auth::login($user, remember: true);

        return redirect()
            ->route('verification.done')
            ->with('status', __('verify.success'));
    }

    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedPhone()) {
            return redirect()->route('verification.done');
        }

        try {
            $this->otp->issue(
                OtpPurpose::Registration,
                $user->phone,
                $user->locale,
                $request->ip(),
            );
        } catch (OtpDeliveryException) {
            // The cooldown and the hourly cap are enforced in the service and
            // audited there; the visitor only learns that they have to wait.
            return back()->withErrors([
                'code' => __('otp.throttled', [
                    'seconds' => (int) config('otp.resend_cooldown_seconds', 60),
                ]),
            ]);
        }

        return back()->with('status', __('otp.resent'));
    }

    /**
     * Where the visitor lands once the number is proven.
     *
     * Separate from `show()` so a reload after a successful verification does not
     * bounce the person back to a form they no longer need, and so the
     * already-verified state has its own URL to bookmark.
     */
    public function done(Request $request): View
    {
        $user = $request->user();

        return view('auth.verified', [
            'user' => $user,
            // Ordering is the only thing the number unlocks, so this is where the
            // button goes rather than a generic dashboard.
            'canOrder' => $user->canRegister(),
        ]);
    }

    private function secondsUntilExpiry(string $phone): ?int
    {
        $record = OtpCode::activeFor(OtpPurpose::Registration, $phone);

        return $record?->secondsRemaining();
    }

    private function attemptsLeft(string $phone): ?int
    {
        $record = OtpCode::activeFor(OtpPurpose::Registration, $phone);

        if ($record === null || $record->max_attempts === 0) {
            return null;
        }

        return max(0, $record->max_attempts - $record->attempts);
    }
}
