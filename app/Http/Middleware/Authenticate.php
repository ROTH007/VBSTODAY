<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        // This app has no 'login' web route — every authenticated route is
        // under /api, so always respond with a 401 JSON error instead of
        // trying to redirect to a route that doesn't exist.
        return null;
    }
}
