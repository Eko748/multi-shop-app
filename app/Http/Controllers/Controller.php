<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    protected array $title = [];
    protected array $label = [
        'Data Master',
        'Transaksi',
        'Rekapitulasi',
        'Retur',
        'Laporan Keuangan',
        'Jurnal Keuangan',
        'Distribusi',
        'Transaksi Digital'
    ];

    protected function saveLogAktivitas(
        string $logName,
        string $subjectType,
        int $subjectId,
        string $event,
        ?array $properties = null,
        ?string $description = null,
        ?string $userId = null,
        ?string $message = null,
    ): void {
        $request = request();

        LogAktivitas::create([
            'log_name'     => $logName,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'event'        => $event,
            'properties'   => $properties ?? [],
            'description'  => $description,
            'user_id'      => $userId ?? Auth::id(),
            'message'      => $message,
            'method'       => $request->method(),
            'route'        => $request->path(),
            'ip_address'   => $request->ip(),
        ]);
    }
}
