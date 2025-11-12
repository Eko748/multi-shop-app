<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class ShareMenuPermissions
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $roleId = $user->id_level;

            $permissions = DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->where('role_has_permissions.role_id', $roleId)
                ->select('permissions.id as permission_id', 'permissions.menu_id')
                ->get();

            View::share('allowedMenus', $permissions->pluck('menu_id')->unique()->toArray());
            View::share('allowedPermissions', $permissions->pluck('permission_id')->unique()->toArray());
        }

        return $next($request);
    }
}
