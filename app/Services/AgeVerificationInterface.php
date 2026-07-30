<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

interface AgeVerificationInterface
{
    public function verify(): RedirectResponse;
    public function resultAgeVerification(Request $request): RedirectResponse;
    public function webhook(Request $request): JsonResponse|RedirectResponse;
}
