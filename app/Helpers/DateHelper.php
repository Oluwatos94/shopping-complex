<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Validator;

class DateHelper
{
    public static function isValidDateFormat(string $date): bool
    {
        return Validator::make(
            ['date' => $date],
            ['date' => 'date_format:Y-m-d']
        )->passes();
    }
}
