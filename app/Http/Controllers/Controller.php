<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @method void authorize(mixed $ability, mixed $arguments = [])
 */
abstract class Controller
{
    use AuthorizesRequests;
}
