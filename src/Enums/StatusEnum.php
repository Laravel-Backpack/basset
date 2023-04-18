<?php

namespace Backpack\Basset\Enums;

enum StatusEnum: string
{
    case LOADED = 'Already loaded';
    case IN_CACHE = 'Already in cache';
    case INTERNALIZED = 'Internalized';
    case INVALID = 'Not in a CDN or local filesystem, falling back to provided path';
    case DISABLED = 'Development mode active';
}
