<?php

namespace App\Class;

use Symfony\Component\HttpFoundation\JsonResponse;

class CustomResponse
{
    public static function success($data = [], $message = 'Success', $code = 200)
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    public static function error($message = 'Error', $errors = [], $code = 400)
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $code);
    }
}
