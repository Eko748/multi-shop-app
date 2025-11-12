<?php

namespace App\Traits;

use App\Constants\HttpStatus;

trait ApiResponse
{
    protected function success($data = null, int $code = 200, ?string $message = null, ?array $pagination = null)
    {
        $response = [
            'code' => $code,
            'status' => HttpStatus::message($code),
            'message' => $message ?: 'Success',
        ];

        if (!is_null($data)) {
            $response['data'] = $data;
        }

        if (!is_null($pagination)) {
            $response['pagination'] = $pagination;
        }

        return response()->json($response, $code);
    }

    protected function error(int $code = 400, ?string $message = null, $errors = null)
    {
        $response = [
            'code' => $code,
            'status' => HttpStatus::message($code),
            'message' => $message ?: 'Error',
        ];

        if (!is_null($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }
}
