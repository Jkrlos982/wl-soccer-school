<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait ApiResponseTrait
{
    /**
     * Success response method
     */
    protected function sendResponse($result, $message = 'Success', $code = Response::HTTP_OK): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $result,
        ];

        return response()->json($response, $code);
    }

    /**
     * Return error response
     */
    protected function sendError($error, $errorMessages = [], $code = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['errors'] = $errorMessages;
        }

        return response()->json($response, $code);
    }

    /**
     * Return validation error response
     */
    protected function sendValidationError($validator): JsonResponse
    {
        return $this->sendError('Validation Error', $validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * Return not found response
     */
    protected function sendNotFound($message = 'Resource not found'): JsonResponse
    {
        return $this->sendError($message, [], Response::HTTP_NOT_FOUND);
    }

    /**
     * Return unauthorized response
     */
    protected function sendUnauthorized($message = 'Unauthorized'): JsonResponse
    {
        return $this->sendError($message, [], Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Return forbidden response
     */
    protected function sendForbidden($message = 'Forbidden'): JsonResponse
    {
        return $this->sendError($message, [], Response::HTTP_FORBIDDEN);
    }

    /**
     * Return internal server error response
     */
    protected function sendInternalError($message = 'Internal Server Error'): JsonResponse
    {
        return $this->sendError($message, [], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Return created response
     */
    protected function sendCreated($result, $message = 'Resource created successfully'): JsonResponse
    {
        return $this->sendResponse($result, $message, Response::HTTP_CREATED);
    }

    /**
     * Return no content response
     */
    protected function sendNoContent(): JsonResponse
    {
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Return accepted response
     */
    protected function sendAccepted($result = null, $message = 'Request accepted'): JsonResponse
    {
        return $this->sendResponse($result, $message, Response::HTTP_ACCEPTED);
    }
}