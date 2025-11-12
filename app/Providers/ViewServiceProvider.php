<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $user = Auth::user();
            if (!$user) return;

            $permissions = DB::table('role_has_permissions')
                ->join('permissions', 'role_has_permissions.permission_id', '=', 'permissions.id')
                ->join('menus', 'permissions.menu_id', '=', 'menus.id')
                ->where('role_has_permissions.role_id', $user->id_level)
                ->select(
                    'permissions.id as permission_id',
                    'permissions.name as permission_name',
                    'menus.id as menu_id',
                    'menus.slug as menu_slug'
                )
                ->get();

            // Gabungkan menu_id dan menu_slug
            $menuIdentifiers = collect();
            foreach ($permissions as $perm) {
                $menuIdentifiers->push($perm->menu_id);
                $menuIdentifiers->push($perm->menu_slug);
            }
            $menuIdentifiers = $menuIdentifiers->unique()->values()->toArray();

            // Gabungkan permission id dan name
            $permissionIdentifiers = collect();
            foreach ($permissions as $perm) {
                $permissionIdentifiers->push($perm->permission_id);
                $permissionIdentifiers->push($perm->permission_name);
            }
            $permissionIdentifiers = $permissionIdentifiers->unique()->values()->toArray();

            // Share ke semua view
            View::share('allowedMenus', $menuIdentifiers);
            View::share('allowedPermissions', $permissionIdentifiers);
        });
    }
}
