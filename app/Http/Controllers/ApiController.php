<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

abstract class ApiController extends Controller
{
    /**
     * Default status code
     */
    protected int $statusCode = 200;

    /**
     * Set the status code
     */
    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Return a success response
     */
    public function successResponse($data = [], ?string $message = null, array $meta = []): JsonResponse
    {
        $response = [
            'success' => true,
            'message' => $message ?? 'Operation successful',
            'data' => $this->transformData($data),
            'timestamp' => now()->toISOString(),
        ];

        if (!empty($meta)) {
            $response['meta'] = $meta;
        }

        return response()->json($response, $this->statusCode);
    }

    /**
     * Return an error response
     */
    public function errorResponse(string $message, int $statusCode = 400, $errors = null, string $errorCode = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        if ($errorCode !== null) {
            $response['error_code'] = $errorCode;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a created response (201)
     */
    public function createdResponse($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->setStatusCode(201)->successResponse($data, $message);
    }

    /**
     * Return an updated response (200)
     */
    public function updatedResponse($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->successResponse($data, $message);
    }

    /**
     * Return a deleted response (200)
     */
    public function deletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->successResponse([], $message);
    }

    /**
     * Return a not found response (404)
     */
    public function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->errorResponse($message, 404, null, 'RESOURCE_NOT_FOUND');
    }

    /**
     * Return a validation error response (422)
     */
    public function validationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->errorResponse($message, 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Return an unauthorized response (401)
     */
    public function unauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->errorResponse($message, 401, null, 'UNAUTHORIZED');
    }

    /**
     * Return a forbidden response (403)
     */
    public function forbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->errorResponse($message, 403, null, 'FORBIDDEN');
    }

    /**
     * Return a server error response (500)
     */
    public function serverErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->errorResponse($message, 500, null, 'SERVER_ERROR');
    }

    /**
     * Return a paginated response
     */
    public function paginatedResponse($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        if ($paginator instanceof LengthAwarePaginator) {
            $data = $this->transformData($paginator->items());

            $meta = [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                    'from' => $paginator->firstItem(),
                    'to' => $paginator->lastItem(),
                    'has_more_pages' => $paginator->hasMorePages(),
                    'next_page_url' => $paginator->nextPageUrl(),
                    'prev_page_url' => $paginator->previousPageUrl(),
                ],
                'links' => [
                    'first' => $paginator->url(1),
                    'last' => $paginator->url($paginator->lastPage()),
                    'prev' => $paginator->previousPageUrl(),
                    'next' => $paginator->nextPageUrl(),
                ],
                'timestamp' => now()->toISOString()
            ];

            return $this->successResponse($data, $message, $meta);
        }

        return $this->successResponse($paginator, $message);
    }

    /**
     * Transform data for consistent response format
     */
    private function transformData($data)
    {
        if ($data instanceof JsonResource) {
            return $data->resolve();
        }

        if ($data instanceof ResourceCollection) {
            return $data->resolve();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        if ($data instanceof LengthAwarePaginator) {
            return $this->transformData($data->items());
        }

        return $data;
    }
}
