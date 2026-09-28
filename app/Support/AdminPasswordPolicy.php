<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class AdminPasswordPolicy
{
    public static function rule(): Password
    {
        return Password::min(12)->mixedCase()->numbers();
    }
}
