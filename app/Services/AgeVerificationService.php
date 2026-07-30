<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\YotiService;
use App\Services\DiditService;

final class AgeVerificationService
{
    public function getProvider(string $provider): AgeVerificationInterface
    {
        // Handle age verification
        return match ($provider) {
            'yoti' => app(YotiService::class),
            'didit' => app(DiditService::class),
            default => throw new \InvalidArgumentException('Invalid age verification provider.'),
        };
    }
}
