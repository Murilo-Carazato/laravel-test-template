<?php

namespace Tests\Unit\Http\Controllers;

use Tests\TestCase;
use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse; // Importar JsonResponse
use Illuminate\Pagination\LengthAwarePaginator; // Importar LengthAwarePaginator
use Illuminate\Database\Eloquent\Collection; // Importar Collection
use Illuminate\Http\Request; // Importar Request
use Illuminate\Http\Resources\Json\JsonResource; // Importar JsonResource
use Illuminate\Http\Resources\Json\ResourceCollection; // Importar ResourceCollection
use Mockery; // Importar Mockery
use Carbon\Carbon; // Importar Carbon


// Testable class that extends ApiController for testing protected/private methods
class TestableApiController extends ApiController
{
    public function callSuccessResponse($data = [], ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->successResponse($data, $message, $meta);
    }

    public function callErrorResponse(string $message, int $statusCode = 400, $errors = null, string $errorCode = null): JsonResponse
    {
        return $this->errorResponse($message, $statusCode, $errors, $errorCode);
    }

    public function callCreatedResponse($data, string $message = 'Resource created successfully'): JsonResponse
    {
        return $this->createdResponse($data, $message);
    }

    public function callUpdatedResponse($data, string $message = 'Resource updated successfully'): JsonResponse
    {
        return $this->updatedResponse($data, $message);
    }

    public function callDeletedResponse(string $message = 'Resource deleted successfully'): JsonResponse
    {
        return $this->deletedResponse($message);
    }

    public function callNotFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->notFoundResponse($message);
    }

    public function callValidationErrorResponse($errors, string $message = 'Validation failed'): JsonResponse
    {
        return $this->validationErrorResponse($errors, $message);
    }

    public function callUnauthorizedResponse(string $message = 'Unauthorized'): JsonResponse
    {
        return $this->unauthorizedResponse($message);
    }

    public function callForbiddenResponse(string $message = 'Forbidden'): JsonResponse
    {
        return $this->forbiddenResponse($message);
    }

    public function callServerErrorResponse(string $message = 'Internal server error'): JsonResponse
    {
        return $this->serverErrorResponse($message);
    }

    public function callPaginatedResponse($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return $this->paginatedResponse($paginator, $message);
    }

    public function callTransformData($data)
    {
        return $this->transformData($data);
    }

    public function callSetStatusCode(int $statusCode): self
    {
        return $this->setStatusCode($statusCode);
    }
}

class ApiControllerTest extends TestCase
{
    protected TestableApiController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new TestableApiController();
        
        // Mock Carbon::now() for consistent timestamp testing
        Carbon::setTestNow(Carbon::parse('2023-01-01 12:00:00'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow(); // Reset Carbon mock
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sets_status_code_correctly(): void
    {
        $controller = $this->controller->callSetStatusCode(201);
        
        $response = $controller->callSuccessResponse(['test' => 'data']);
        
        $this->assertEquals(201, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_successful_response_with_default_message(): void
    {
        $response = $this->controller->callSuccessResponse(['key' => 'value']);
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Operation successful',
            'data' => ['key' => 'value'],
            'timestamp' => '2023-01-01T12:00:00.000000Z'
        ]);
    }

    /** @test */
    public function it_returns_successful_response_with_custom_message(): void
    {
        $response = $this->controller->callSuccessResponse(['key' => 'value'], 'Custom success message');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Custom success message',
            'data' => ['key' => 'value']
        ]);
    }

    /** @test */
    public function it_returns_successful_response_with_meta_data(): void
    {
        $meta = ['version' => '1.0', 'api_limit' => 100];
        $response = $this->controller->callSuccessResponse(['key' => 'value'], 'Test Message', $meta);
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Test Message',
            'data' => ['key' => 'value'],
            'meta' => $meta
        ]);
    }

    /** @test */
    public function it_returns_successful_response_without_meta_when_empty(): void
    {
        $response = $this->controller->callSuccessResponse(['key' => 'value'], 'Test Message', []);
        
        $responseData = $response->getData(true);
        $this->assertArrayNotHasKey('meta', $responseData);
    }

    /** @test */
    public function it_returns_error_response_with_minimum_data(): void
    {
        $response = $this->controller->callErrorResponse('Error Message');
        
        $this->assertEquals(400, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Error Message',
            'timestamp' => '2023-01-01T12:00:00.000000Z'
        ]);
        
        $responseData = $response->getData(true);
        $this->assertArrayNotHasKey('errors', $responseData);
        $this->assertArrayNotHasKey('error_code', $responseData);
    }

    /** @test */
    public function it_returns_error_response_with_full_data(): void
    {
        $errors = ['field' => 'error message'];
        $response = $this->controller->callErrorResponse('Error Message', 422, $errors, 'VALIDATION_ERROR');
        
        $this->assertEquals(422, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Error Message',
            'errors' => $errors,
            'error_code' => 'VALIDATION_ERROR'
        ]);
    }

    /** @test */
    public function it_returns_created_response_with_default_message(): void
    {
        $data = ['id' => 1, 'name' => 'New Resource'];
        $response = $this->controller->callCreatedResponse($data);
        
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Resource created successfully',
            'data' => $data
        ]);
    }

    /** @test */
    public function it_returns_created_response_with_custom_message(): void
    {
        $data = ['id' => 1];
        $response = $this->controller->callCreatedResponse($data, 'User created successfully');
        
        $this->assertEquals(201, $response->getStatusCode());
        $response->assertJson([
            'message' => 'User created successfully'
        ]);
    }

    /** @test */
    public function it_returns_updated_response(): void
    {
        $data = ['id' => 1, 'name' => 'Updated Resource'];
        $response = $this->controller->callUpdatedResponse($data, 'Resource updated');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Resource updated',
            'data' => $data
        ]);
    }

    /** @test */
    public function it_returns_deleted_response(): void
    {
        $response = $this->controller->callDeletedResponse('Resource deleted');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Resource deleted',
            'data' => []
        ]);
    }

    /** @test */
    public function it_returns_not_found_response(): void
    {
        $response = $this->controller->callNotFoundResponse();
        
        $this->assertEquals(404, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Resource not found',
            'error_code' => 'RESOURCE_NOT_FOUND'
        ]);
    }

    /** @test */
    public function it_returns_not_found_response_with_custom_message(): void
    {
        $response = $this->controller->callNotFoundResponse('User not found');
        
        $this->assertEquals(404, $response->getStatusCode());
        $response->assertJson([
            'message' => 'User not found'
        ]);
    }

    /** @test */
    public function it_returns_validation_error_response(): void
    {
        $errors = ['field' => ['The field is required.']];
        $response = $this->controller->callValidationErrorResponse($errors);
        
        $this->assertEquals(422, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $errors,
            'error_code' => 'VALIDATION_ERROR'
        ]);
    }

    /** @test */
    public function it_returns_validation_error_response_with_custom_message(): void
    {
        $errors = ['email' => ['Invalid email format']];
        $response = $this->controller->callValidationErrorResponse($errors, 'Form validation failed');
        
        $response->assertJson([
            'message' => 'Form validation failed'
        ]);
    }

    /** @test */
    public function it_returns_unauthorized_response(): void
    {
        $response = $this->controller->callUnauthorizedResponse();
        
        $this->assertEquals(401, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Unauthorized',
            'error_code' => 'UNAUTHORIZED'
        ]);
    }

    /** @test */
    public function it_returns_forbidden_response(): void
    {
        $response = $this->controller->callForbiddenResponse();
        
        $this->assertEquals(403, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Forbidden',
            'error_code' => 'FORBIDDEN'
        ]);
    }

    /** @test */
    public function it_returns_server_error_response(): void
    {
        $response = $this->controller->callServerErrorResponse();
        
        $this->assertEquals(500, $response->getStatusCode());
        $response->assertJson([
            'success' => false,
            'message' => 'Internal server error',
            'error_code' => 'SERVER_ERROR'
        ]);
    }

    /** @test */
    public function it_returns_paginated_response_with_length_aware_paginator(): void
    {
        $items = collect([['id' => 1, 'name' => 'Item 1'], ['id' => 2, 'name' => 'Item 2']]);
        $paginator = new LengthAwarePaginator(
            $items,
            20, // total
            10, // perPage
            1,  // currentPage
            ['path' => 'http://localhost/items']
        );

        $response = $this->controller->callPaginatedResponse($paginator, 'Paginated data');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Paginated data',
            'data' => [
                ['id' => 1, 'name' => 'Item 1'],
                ['id' => 2, 'name' => 'Item 2']
            ]
        ]);

        $responseData = $response->getData(true);
        $this->assertArrayHasKey('meta', $responseData);
        $this->assertArrayHasKey('pagination', $responseData['meta']);
        
        $pagination = $responseData['meta']['pagination'];
        $this->assertEquals(1, $pagination['current_page']);
        $this->assertEquals(2, $pagination['last_page']);
        $this->assertEquals(10, $pagination['per_page']);
        $this->assertEquals(20, $pagination['total']);
        $this->assertEquals(1, $pagination['from']);
        $this->assertEquals(2, $pagination['to']);
        $this->assertTrue($pagination['has_more_pages']);
    }

    /** @test */
    public function it_returns_paginated_response_with_non_paginator_data(): void
    {
        $data = ['simple' => 'data'];
        $response = $this->controller->callPaginatedResponse($data, 'Simple data');
        
        $this->assertEquals(200, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'message' => 'Simple data',
            'data' => $data
        ]);
        
        $responseData = $response->getData(true);
        $this->assertArrayNotHasKey('meta', $responseData);
    }

    /** @test */
    public function it_transforms_json_resource_data(): void
    {
        $resource = Mockery::mock(JsonResource::class);
        $resource->shouldReceive('resolve')->once()->andReturn(['id' => 1, 'name' => 'Test']);
        
        $transformed = $this->controller->callTransformData($resource);
        
        $this->assertEquals(['id' => 1, 'name' => 'Test'], $transformed);
    }

    /** @test */
    public function it_transforms_resource_collection_data(): void
    {
        $collection = Mockery::mock(ResourceCollection::class);
        $collection->shouldReceive('resolve')->once()->andReturn([['id' => 1], ['id' => 2]]);
        
        $transformed = $this->controller->callTransformData($collection);
        
        $this->assertEquals([['id' => 1], ['id' => 2]], $transformed);
    }

    /** @test */
    public function it_transforms_eloquent_collection_data(): void
    {
        $collection = new Collection([['id' => 1], ['id' => 2]]);
        
        $transformed = $this->controller->callTransformData($collection);
        
        $this->assertEquals([['id' => 1], ['id' => 2]], $transformed);
    }

    /** @test */
    public function it_transforms_length_aware_paginator_data(): void
    {
        $items = collect([['id' => 1], ['id' => 2]]);
        $paginator = new LengthAwarePaginator($items, 2, 10, 1);
        
        $transformed = $this->controller->callTransformData($paginator);
        
        $this->assertEquals([['id' => 1], ['id' => 2]], $transformed);
    }

    /** @test */
    public function it_transforms_raw_array_data(): void
    {
        $data = ['id' => 1, 'name' => 'Raw Data'];
        
        $transformed = $this->controller->callTransformData($data);
        
        $this->assertEquals(['id' => 1, 'name' => 'Raw Data'], $transformed);
    }

    /** @test */
    public function it_transforms_null_data(): void
    {
        $transformed = $this->controller->callTransformData(null);
        
        $this->assertNull($transformed);
    }

    /** @test */
    public function it_transforms_empty_collection(): void
    {
        $collection = new Collection([]);
        
        $transformed = $this->controller->callTransformData($collection);
        
        $this->assertEquals([], $transformed);
    }

    /** @test */
    public function it_returns_response_with_correct_content_type(): void
    {
        $response = $this->controller->callSuccessResponse(['test' => 'data']);
        
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_chains_status_code_setting_with_response_methods(): void
    {
        $response = $this->controller->callSetStatusCode(418)->callSuccessResponse(['teapot' => true]);
        
        $this->assertEquals(418, $response->getStatusCode());
        $response->assertJson([
            'success' => true,
            'data' => ['teapot' => true]
        ]);
    }

    /** @test */
    public function it_handles_complex_nested_data_structures(): void
    {
        $complexData = [
            'user' => [
                'id' => 1,
                'profile' => [
                    'name' => 'John Doe',
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => true
                    ]
                ]
            ],
            'permissions' => ['read', 'write']
        ];
        
        $response = $this->controller->callSuccessResponse($complexData);
        
        $response->assertJson([
            'data' => $complexData
        ]);
    }

    /** @test */
    public function it_handles_empty_data_array(): void
    {
        $response = $this->controller->callSuccessResponse([]);
        
        $response->assertJson([
            'success' => true,
            'data' => []
        ]);
    }

    /** @test */
    public function it_preserves_timestamp_format_in_responses(): void
    {
        $response = $this->controller->callSuccessResponse(['test' => 'data']);
        
        $responseData = $response->getData(true);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/',
            $responseData['timestamp']
        );
    }
}