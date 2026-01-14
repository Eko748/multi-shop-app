<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class UserIdentify
{
    /**
     * Identifikasi apakah user berhak mengakses data toko.
     *
     * @param int|null $dataTokoId    toko_id dari item / data
     * @param int|null $requestTokoId toko_id dari request
     * @return bool
     */
    public static function access(?int $dataTokoId, ?int $requestTokoId): bool
    {
        if (!$dataTokoId || !$requestTokoId) {
            return false;
        }

        // ✅ Case normal (default)
        if ($dataTokoId === $requestTokoId) {
            return true;
        }

        /**
         * 🔐 FUTURE CASE (belum aktif)
         * ------------------------------------------------
         * Jika nanti ada user khusus:
         * - Super Admin
         * - Auditor
         * - User Pusat
         *
         * contoh:
         * if (self::isPrivilegedUser()) {
         *     return true;
         * }
         */

        return false;
    }

    /**
     * Identifikasi user spesial (future use).
     */
    protected static function isPrivilegedUser(): bool
    {
        $user = Auth::user();

        // ⛔ sementara false
        return false;

        // 🔜 nanti bisa:
        // return in_array($user->role, ['superadmin', 'auditor']);
    }
}
