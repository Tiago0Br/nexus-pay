<?php

namespace App\Enums;

enum ErrorCode: string
{
    case UNAUTHORIZED = 'UNAUTHORIZED';
    case VALIDATION_FAILED = 'VALIDATION_FAILED';
    case NOT_FOUND = 'NOT_FOUND';
    case INTERNAL_ERROR = 'INTERNAL_ERROR';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
