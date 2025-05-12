<?php

namespace App\Http\Controllers\Api\Traits;

trait HasApiResponses
{
    /**
     * Success Response
     */
    protected function successResponse($data = [], $message = 'Success', $code = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Error Response
     */
    protected function errorResponse($message = 'Error', $code = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $code);
    }

    /**
     * Not Found Response
     */
    protected function notFoundResponse($message = 'Resource not found')
    {
        return $this->errorResponse($message, 404);
    }

    /**
     * Unauthorized Response
     */
    protected function unauthorizedResponse($message = 'Unauthorized')
    {
        return $this->errorResponse($message, 401);
    }

    /**
     * Forbidden Response
     */
    protected function forbiddenResponse($message = 'Forbidden')
    {
        return $this->errorResponse($message, 403);
    }
} 