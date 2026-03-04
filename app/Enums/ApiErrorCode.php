<?php

namespace App\Enums;

enum ApiErrorCode: string
{
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case UNAUTHENTICATED   = 'UNAUTHENTICATED';
    case FORBIDDEN         = 'FORBIDDEN';
    case NOT_FOUND         = 'NOT_FOUND';
    case CONFLICT          = 'CONFLICT';
    case SERVER_ERROR      = 'SERVER_ERROR';

    // Domain-specific examples (add as needed)
    case PROJECT_NOT_FOUND = 'PROJECT_NOT_FOUND';
    case INVALID_STATUS    = 'INVALID_STATUS';
}
