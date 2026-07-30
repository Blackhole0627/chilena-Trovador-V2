<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AgeVerificationService;

class AgeVerificationController extends Controller
{
    protected $provider;

    public function __construct(
        protected AgeVerificationService $ageVerificationService,
    ) {
        $this->provider = config('settings.age_verification_provider');
    }

    public function startAgeVerification()
    {
        if (auth()->user()->age_verification != 0 || auth()->user()->role === 'admin') {
            return redirect()->route('verify.age')->withErrorVerification(__('general.alert_error_age_verification'));
        }

        if (!config('settings.age_verification_status')) {
            return redirect()->route('home');
        }

        try {
            return $this->ageVerificationService
                ->getProvider($this->provider)
                ->verify();
        } catch (\Exception $e) {
            return redirect()
                ->route('verify.age')
                ->withErrorVerification($e->getMessage());
        }
    }

    public function resultAgeVerification(Request $request)
    {
        try {
            return $this->ageVerificationService
                ->getProvider($this->provider)
                ->resultAgeVerification($request);
        } catch (\Exception $e) {
            return redirect()->route('verify.age')->withErrorVerification($e->getMessage());
        }
    }

    public function webhook(Request $request)
    {
        return $this->ageVerificationService
            ->getProvider($this->provider)
            ->webhook($request);
    }
}
