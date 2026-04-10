<?php

namespace App\Helpers;

class LogHelper
{
    public static function buildChanges(array $old = [], array $new = []): array
    {
        return [
            'changes' => [
                'old' => $old,
                'new' => $new,
            ],
        ];
    }
}
