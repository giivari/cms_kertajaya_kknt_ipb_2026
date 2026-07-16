<?php

namespace App\Enums;

enum ComponentType: string
{
    case HEADING = 'heading';
    case RICH_TEXT = 'rich_text';
    case IMAGE = 'image';
    case GALLERY = 'gallery';
    case STATISTICS = 'statistics';
    case VIDEO = 'video';
    case MAP = 'map';
    case DOCUMENTS = 'documents';
    case CTA_BUTTON = 'cta_button';
    case CARD_GRID = 'card_grid';
    case CONTACT_BLOCK = 'contact_block';
}
