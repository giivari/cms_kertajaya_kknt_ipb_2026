<?php

namespace App\Enums;

enum DerivativeType: string
{
    case PROTECTED_MASTER = 'protected_master';
    case OPTIMIZED = 'optimized';
    case THUMBNAIL = 'thumbnail';
    case PUBLIC = 'public';
    case PUBLIC_VISIBLE_WATERMARK = 'public_visible_watermark';
    case PDF_PREVIEW = 'pdf_preview';
}
