<?php

namespace Jinya\Router\Extensions\Database;

use DateTime;
use Jinya\Router\Extensions\Database\Classes\JustFindable;
use Jinya\Router\Extensions\Database\Classes\NonFindable;
use Jinya\Router\Extensions\Database\Classes\ReferencedTestEntity;
use Jinya\Router\Extensions\Database\Classes\TestEntity;
use Jinya\Router\Extensions\Database\Extensions\MigratingTestCase;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class DatabaseRequestHandlerTest extends MigratingTestCase
{
    private function getErrorHandler(): ErrorHandler
    {
        return new StatusErrorHandler();
    }

    private function getDummyRequest(string $method, string $uri): ServerRequestInterface
    {
        return new ServerRequest($method, $uri);
    }

    public function testHandleDeleteRequest(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('DELETE', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleDeleteRequest($request, ReferencedTestEntity::class, 1);
        self::assertEquals(204, $response->getStatusCode());
    }

    public function testHandleDeleteRequestNotFound(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('DELETE', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleDeleteRequest($request, TestEntity::class, -1);
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testHandleDeleteRequestReferenceFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('DELETE', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleDeleteRequest($request, TestEntity::class, 1);
        self::assertEquals(409, $response->getStatusCode());
    }

    public function testHandleDeleteRequestNonFindableClass(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('DELETE', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleDeleteRequest($request, NonFindable::class, 1);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testHandleDeleteRequestNonDeletableClass(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('DELETE', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleDeleteRequest($request, JustFindable::class, 1);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testHandleCreateRequest(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2', 'date' => (new DateTime())->format(DATE_ATOM)];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
        ]);
        self::assertEquals(201, $response->getStatusCode());
    }

    public function testHandleCreateRequestDefaultValue(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2'];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => false,
                'type' => DateTime::class,
                'default' => new DateTime()
            ],
        ]);
        self::assertEquals(201, $response->getStatusCode());
    }

    public function testHandleCreateRequestMissingFieldsAll(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $request = $this->getDummyRequest('POST', '')
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
        ]);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testHandleCreateRequestMissingFieldsSome(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2'];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
        ]);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testHandleCreateDateWrongFormat(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2', 'date' => (new DateTime())->format(DATE_COOKIE)];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
        ]);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testHandleCreateUniqueFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 11', 'displayName' => 'Test 25 2', 'date' => (new DateTime())->format(DATE_ATOM)];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
        ]);
        self::assertEquals(409, $response->getStatusCode());
    }

    public function testHandleCreateNotNullFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 52', 'displayName' => null, 'date' => (new DateTime())->format(DATE_ATOM)];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, TestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => false,
                'type' => DateTime::class,
            ],
        ]);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testHandleCreateReferenceFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = [
            'name' => 'Test 52',
            'displayName' => 'Test 25 2',
            'date' => (new DateTime())->format(DATE_ATOM),
            'testEntityId' => -1
        ];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, ReferencedTestEntity::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
            'testEntityId' => [
                'required' => true,
                'type' => 'int',
            ],
        ]);
        self::assertEquals(409, $response->getStatusCode());
    }

    public function testHandleCreateNotCreatable(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = [
            'name' => 'Test 52',
            'displayName' => 'Test 25 2',
            'date' => (new DateTime())->format(DATE_ATOM),
            'testEntityId' => -1
        ];
        $request = $this->getDummyRequest('POST', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleCreateRequest($request, JustFindable::class, [
            'name' => [
                'required' => true,
                'type' => 'string',
            ],
            'displayName' => [
                'required' => true,
                'type' => 'string',
            ],
            'date' => [
                'required' => true,
                'type' => DateTime::class,
            ],
            'testEntityId' => [
                'required' => true,
                'type' => 'int',
            ],
        ]);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testHandleUpdateRequest(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2', 'date' => (new DateTime())->format(DATE_ATOM)];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, TestEntity::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
        ], 1);
        self::assertEquals(204, $response->getStatusCode());
    }

    public function testHandleUpdateRequestNotAllValues(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2'];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, TestEntity::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
        ], 1);
        self::assertEquals(204, $response->getStatusCode());
    }

    public function testHandleUpdateDateWrongFormat(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 25', 'displayName' => 'Test 25 2', 'date' => (new DateTime())->format(DATE_COOKIE)];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, TestEntity::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
        ], 1);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testHandleUpdateUniqueFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 12', 'displayName' => 'Test 25 2', 'date' => (new DateTime())->format(DATE_ATOM)];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, TestEntity::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
        ], 1);
        self::assertEquals(409, $response->getStatusCode());
    }

    public function testHandleUpdateNotNullFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = ['name' => 'Test 52', 'displayName' => null, 'date' => (new DateTime())->format(DATE_ATOM)];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, TestEntity::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
        ], 1);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function testHandleUpdateReferenceFailed(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = [
            'name' => 'Test 52',
            'displayName' => 'Test 25 2',
            'date' => (new DateTime())->format(DATE_ATOM),
            'testEntityId' => -1
        ];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, ReferencedTestEntity::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
            'testEntityId' => [
                'type' => 'int',
            ],
        ], 1);
        self::assertEquals(409, $response->getStatusCode());
    }

    public function testHandleUpdateNotFindable(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = [
            'name' => 'Test 52',
            'displayName' => 'Test 25 2',
            'date' => (new DateTime())->format(DATE_ATOM),
            'testEntityId' => -1
        ];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, NonFindable::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
            'testEntityId' => [
                'type' => 'int',
            ],
        ], 1);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testHandleUpdateNotUpdatable(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $handler = new DatabaseRequestHandler();
        $body = [
            'name' => 'Test 52',
            'displayName' => 'Test 25 2',
            'date' => (new DateTime())->format(DATE_ATOM),
            'testEntityId' => -1
        ];
        $request = $this->getDummyRequest('PUT', '')
            ->withParsedBody($body)
            ->withAddedHeader('Content-Type', 'application/json');
        $response = $handler->handleUpdateRequest($request, JustFindable::class, [
            'name' => [
                'type' => 'string',
            ],
            'displayName' => [
                'type' => 'string',
            ],
            'date' => [
                'type' => DateTime::class,
            ],
            'testEntityId' => [
                'type' => 'int',
            ],
        ], 1);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testHandleGetByIdRequest(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $entity = ReferencedTestEntity::findById(1);

        $request = $this->getDummyRequest('GET', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleGetByIdRequest($request, ReferencedTestEntity::class, 1);
        self::assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $data = json_decode($response->getBody()->getContents(), true);
        self::assertEquals($entity->id, $data['id']);
        self::assertEquals($entity->name, $data['name']);
        self::assertEquals($entity->displayName, $data['displayName']);
        self::assertEquals($entity->date->format(DATE_ATOM), $data['date']);
        self::assertEquals($entity->testEntityId, $data['testEntityId']);
    }

    public function testHandleGetByIdRequestNotFound(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('GET', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleGetByIdRequest($request, TestEntity::class, -1);
        self::assertEquals(404, $response->getStatusCode());
    }

    public function testHandleGetByIdRequestNonFindableClass(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('GET', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleGetByIdRequest($request, NonFindable::class, 1);
        self::assertEquals(500, $response->getStatusCode());
    }

    public function testHandleGetAllRequest(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('GET', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleGetAllRequest($request, ReferencedTestEntity::class);
        self::assertEquals(200, $response->getStatusCode());

        $response->getBody()->rewind();
        $responseAllData = $response->getBody()->getContents();
        $data = json_decode($responseAllData, true);
        self::assertEquals(10, $data['totalCount']);
        self::assertEquals(10, $data['itemsCount']);
        self::assertEquals(0, $data['offset']);
        self::assertCount(10, $data['items']);
        self::assertArrayHasKey('id', $data['items'][0]);
        self::assertArrayHasKey('displayName', $data['items'][0]);

        $request = $this->getDummyRequest('GET', '')->withQueryParams(
            ['orderBy' => 'display_name', 'orderDirection' => 'DESC']
        );
        $response = $handler->handleGetAllRequest($request, ReferencedTestEntity::class);
        $response->getBody()->rewind();
        $responseAllDataOrdered = $response->getBody()->getContents();
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEquals($responseAllData, $responseAllDataOrdered);

        $request = $this->getDummyRequest('GET', '')->withQueryParams(['offset' => 3]);
        $response = $handler->handleGetAllRequest($request, ReferencedTestEntity::class);
        $response->getBody()->rewind();
        $responseAllDataOffset = $response->getBody()->getContents();
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEquals($responseAllData, $responseAllDataOffset);

        $request = $this->getDummyRequest('GET', '')->withQueryParams(['offset' => 3, 'count' => 2]);
        $response = $handler->handleGetAllRequest($request, ReferencedTestEntity::class);
        $response->getBody()->rewind();
        $responseAllDataOffsetLimit = $response->getBody()->getContents();
        self::assertEquals(200, $response->getStatusCode());
        self::assertNotEquals($responseAllDataOffset, $responseAllDataOffsetLimit);
    }

    public function testHandleGetAllRequestNonFindableClass(): void
    {
        Handlers::$errorHandler = $this->getErrorHandler();

        $request = $this->getDummyRequest('GET', '');
        $handler = new DatabaseRequestHandler();
        $response = $handler->handleGetAllRequest($request, NonFindable::class);
        self::assertEquals(500, $response->getStatusCode());
    }
}
