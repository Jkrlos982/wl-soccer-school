<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use League\Fractal\TransformerAbstract;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Illuminate\Pagination\LengthAwarePaginator;

class BaseController extends Controller
{
    protected Manager $fractal;
    
    public function __construct()
    {
        $this->fractal = new Manager();
        
        // Parse includes from request
        if ($includes = request()->get('include')) {
            $this->fractal->parseIncludes($includes);
        }
    }
    
    /**
     * Transform a single item
     */
    protected function respondWithItem($item, TransformerAbstract $transformer, string $resourceKey = null): JsonResponse
    {
        $resource = new Item($item, $transformer, $resourceKey);
        $data = $this->fractal->createData($resource)->toArray();
        
        return $this->respondWithSuccess($data);
    }
    
    /**
     * Transform a collection
     */
    protected function respondWithCollection($collection, TransformerAbstract $transformer, string $resourceKey = null): JsonResponse
    {
        $resource = new Collection($collection, $transformer, $resourceKey);
        $data = $this->fractal->createData($resource)->toArray();
        
        return $this->respondWithSuccess($data);
    }
    
    /**
     * Transform a paginated collection
     */
    protected function respondWithPaginatedCollection(LengthAwarePaginator $paginator, TransformerAbstract $transformer, string $resourceKey = null): JsonResponse
    {
        $collection = $paginator->getCollection();
        $resource = new Collection($collection, $transformer, $resourceKey);
        $resource->setPaginator(new IlluminatePaginatorAdapter($paginator));
        
        $data = $this->fractal->createData($resource)->toArray();
        
        return $this->respondWithSuccess($data);
    }
    
    /**
     * Success response
     */
    protected function respondWithSuccess($data = null, string $message = 'Success', int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Error response
     */
    protected function respondWithError(string $message, int $statusCode = 400, string $errorCode = null, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];
        
        if ($errorCode) {
            $response['error_code'] = $errorCode;
        }
        
        if ($errors) {
            $response['errors'] = $errors;
        }
        
        return response()->json($response, $statusCode);
    }
    
    /**
     * Validation error response
     */
    protected function respondWithValidationError($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->respondWithError($message, 422, 'VALIDATION_ERROR', $errors);
    }
    
    /**
     * Not found response
     */
    protected function respondWithNotFound(string $message = 'Resource not found'): JsonResponse
    {
        return $this->respondWithError($message, 404, 'NOT_FOUND');
    }
    
    /**
     * Unauthorized response
     */
    protected function respondWithUnauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->respondWithError($message, 401, 'UNAUTHORIZED');
    }
    
    /**
     * Forbidden response
     */
    protected function respondWithForbidden(string $message = 'Forbidden'): JsonResponse
    {
        return $this->respondWithError($message, 403, 'FORBIDDEN');
    }
    
    /**
     * Internal server error response
     */
    protected function respondWithInternalError(string $message = 'Internal server error'): JsonResponse
    {
        return $this->respondWithError($message, 500, 'INTERNAL_ERROR');
    }
}