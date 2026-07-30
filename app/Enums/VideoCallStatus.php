<?php

namespace App\Enums;

enum VideoCallStatus: string
{
    case CALLING = 'calling';
    case ACCEPTED = 'accepted';
    case REJECTED = 'rejected';
    case CANCELED = 'canceled';
    case UNANSWERED = 'unanswered';

    public function locale(): string
    {
        return match ($this) {
            self::ACCEPTED => __('general.accepted'),
            self::REJECTED => __('general.rejected'),
            self::CANCELED => __('general.canceled'),
            self::UNANSWERED => __('general.unanswered'),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::ACCEPTED => 'success',
            self::REJECTED => 'danger',
            self::CANCELED => 'danger',
            self::UNANSWERED => 'secondary',
        };
    }
}
