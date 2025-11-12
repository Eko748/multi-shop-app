<?php

use Illuminate\Support\Facades\View;

if (!function_exists('hasMenu')) {
    function hasMenu($identifier)
    {
        $menus = View::getShared()['allowedMenus'] ?? [];
        return in_array((string)$identifier, array_map('strval', $menus), true);
    }
}

if (!function_exists('hasPermission')) {
    function hasPermission($identifier)
    {
        $permissions = View::getShared()['allowedPermissions'] ?? [];
        return in_array((string)$identifier, array_map('strval', $permissions), true);
    }
}

if (!function_exists('hasAnyMenu')) {
    function hasAnyMenu(array $identifiers)
    {
        $menus = View::getShared()['allowedMenus'] ?? [];
        $menusStr = array_map('strval', $menus);
        $identifiersStr = array_map('strval', $identifiers);
        return !empty(array_intersect($identifiersStr, $menusStr));
    }
}

if (!function_exists('hasAnyPermission')) {
    function hasAnyPermission(array $identifiers)
    {
        $permissions = View::getShared()['allowedPermissions'] ?? [];
        $permissionsStr = array_map('strval', $permissions);
        $identifiersStr = array_map('strval', $identifiers);
        return !empty(array_intersect($identifiersStr, $permissionsStr));
    }
}
