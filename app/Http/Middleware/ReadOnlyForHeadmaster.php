<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReadOnlyForHeadmaster
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === Role::Kepala) {
            $isSafeMethod = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);

            // Pengecualian aksi yang diizinkan untuk kepala (logout & update password mandiri)
            $isAllowedAction = $request->routeIs('logout', 'password.update');

            if (! $isSafeMethod && ! $isAllowedAction) {
                abort(403, 'Akses hanya baca. Kepala madrasah tidak memiliki izin untuk mengubah data.');
            }
        }

        return $next($request);
    }
}
