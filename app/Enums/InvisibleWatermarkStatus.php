<?php

namespace App\Enums;

enum InvisibleWatermarkStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case APPLIED = 'applied';
    case VERIFIED = 'verified';
    case FAILED = 'failed';
    case UNSUPPORTED = 'unsupported';
}
