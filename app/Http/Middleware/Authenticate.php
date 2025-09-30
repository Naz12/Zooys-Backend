<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        if (! $request->expectsJson()) {
            // Redirect any admin URL
            if ($request->is('admin') || $request->is('admin/*')) {
                return route('admin.login');
            }

            // If you add frontend user login later:
            // return route('login');

            return route('admin.login'); // default for now
        }

        return null;
    }

}