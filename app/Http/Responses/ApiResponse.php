<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Return a successful JSON response.
     *
     * @param mixed|null $data
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    public static function success(
        mixed $data = null,
        string $message = 'Success',
        int $statusCode = Response::HTTP_OK,
        array $headers = []
    ): JsonResponse {
        $responseData = [
            'success' => true,
            'message' => $message,
            'data' => static::formatData($data),
            'status_code' => $statusCode,
        ];

        // Handle pagination meta data if present
        if ($data instanceof LengthAwarePaginator || $data instanceof ResourceCollection && $data->resource instanceof LengthAwarePaginator) {
            $paginationData = static::extractPaginationData($data);
            if ($paginationData) {
                $responseData['data']['pagination'] = $paginationData;
            }
        }

        return response()->json($responseData, $statusCode, $headers);
    }

    /**
     * Return an error JSON response.
     *
     * @param string $message
     * @param int $statusCode
     * @param mixed|null $errors Can be an array, object, or string
     * @param array $headers
     * @return JsonResponse
     */
    public static function error(
        string $message = 'Error',
        int $statusCode = Response::HTTP_BAD_REQUEST, // Use constants
        mixed $errors = null,
        array $headers = []
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors, // Keep errors separate from data
            'status_code' => $statusCode,
        ], $statusCode, $headers);
    }

    // --- Convenience Methods ---

    /**
     * Return a 201 Created response.
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return static::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Return a 200 OK response with No Content response.
     * Typically used for successful deletions or updates with no body.
     */
    public static function noContent(string $message = 'Operation successful'): JsonResponse
    {
        // Note: 204 should technically have no body, but a consistent structure might be preferred.
        return static::success(null, $message, Response::HTTP_OK);
    }


    /**
     * Return a 400 Bad Request response.
     */
    public static function badRequest(string $message = 'Bad Request', mixed $errors = null): JsonResponse
    {
        return static::error($message, Response::HTTP_BAD_REQUEST, $errors);
    }

    /**
     * Return a 401 Unauthorized response.
     */
    public static function unauthorized(string $message = 'Unauthorized', mixed $errors = null): JsonResponse
    {
        return static::error($message, Response::HTTP_UNAUTHORIZED, $errors);
    }

    /**
     * Return a 403 Forbidden response.
     */
    public static function forbidden(string $message = 'Forbidden', mixed $errors = null): JsonResponse
    {
        return static::error($message, Response::HTTP_FORBIDDEN, $errors);
    }

    /**
     * Return a 404 Not Found response.
     */
    public static function notFound(string $message = 'Resource not found', mixed $errors = null): JsonResponse
    {
        return static::error($message, Response::HTTP_NOT_FOUND, $errors);
    }

    /**
     * Return a 422 Unprocessable Entity response (Validation Failed).
     */
    public static function validationError(mixed $errors, string $message = 'Validation failed'): JsonResponse
    {
        return static::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }

    /**
     * Return a 500 Internal Server Error response.
     */
    public static function serverError(string $message = 'Internal Server Error', mixed $errors = null): JsonResponse
    {
        // Optionally log the error here in a real application
        // Log::error($message, ['errors' => $errors]);
        return static::error($message, Response::HTTP_INTERNAL_SERVER_ERROR, $errors);
    }


    // --- Helper Methods ---

    /**
     * Format data for the response, handling various types.
     *
     * @param mixed $data
     * @return mixed
     */
    protected static function formatData(mixed $data): mixed
    {
        if ($data instanceof JsonResource && !$data instanceof ResourceCollection) {
            // Return the resource's array representation directly
            // Use resolve() to ensure it's processed correctly, especially with conditional attributes
            return $data->resolve();
        } elseif ($data instanceof ResourceCollection) {
            // For collections (including paginated ones), return the collection's resolved data
            return ['list' => $data->resolve()];
        } elseif ($data instanceof LengthAwarePaginator) {
            // If it's a raw paginator, get the items
            return ['list' => $data->items()];
        } elseif ($data instanceof Arrayable) {
            return $data->toArray();
        }
        // Return primitive types or other objects as is
        return $data;
    }

    /**
     * Extract pagination details from Paginator or ResourceCollection.
     *
     * @param LengthAwarePaginator|ResourceCollection $paginator
     * @return array|null
     */
    protected static function extractPaginationData(LengthAwarePaginator|ResourceCollection $paginator): ?array
    {
        $paginationInstance = null;

        if ($paginator instanceof LengthAwarePaginator) {
            $paginationInstance = $paginator;
        } elseif ($paginator instanceof ResourceCollection && $paginator->resource instanceof LengthAwarePaginator) {
            $paginationInstance = $paginator->resource;
        }

        if ($paginationInstance) {
            return [
                'total' => $paginationInstance->total(),
                'per_page' => $paginationInstance->perPage(),
                'current_page' => $paginationInstance->currentPage(),
                'last_page' => $paginationInstance->lastPage(),
                // 'from' => $paginationInstance->firstItem(),
                // 'to' => $paginationInstance->lastItem(),
                // 'first_page_url' => $paginationInstance->url(1),
                // 'last_page_url' => $paginationInstance->url($paginationInstance->lastPage()),
                // 'next_page_url' => $paginationInstance->nextPageUrl(),
                // 'prev_page_url' => $paginationInstance->previousPageUrl(),
                // 'path' => $paginationInstance->path(),
            ];
        }

        return null;
    }
}
