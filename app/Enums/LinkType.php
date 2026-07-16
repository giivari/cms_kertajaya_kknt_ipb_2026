<?php

namespace App\Enums;

enum LinkType: string
{
    case PAGE = 'page';
    case NEWS_INDEX = 'news_index';
    case GALLERY_INDEX = 'gallery_index';
    case DOCUMENT_INDEX = 'document_index';
    case MAP = 'map';
    case CONTACT = 'contact';
    case CUSTOM = 'custom';
}
