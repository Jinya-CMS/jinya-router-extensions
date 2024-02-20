<?php

namespace Jinya\Router\Extensions\Database;

use Iterator;
use Jinya\Database\Creatable;
use Jinya\Database\Deletable;
use Jinya\Database\Entity;
use Jinya\Database\Findable;
use Jinya\Database\Updatable;
use Jinya\Router\Extensions\Database\Exceptions\CreateColumnIsNullException;
use Jinya\Router\Extensions\Database\Exceptions\CreateReferenceFailedException;
use Jinya\Router\Extensions\Database\Exceptions\CreateUniqueFailedException;
use Jinya\Router\Extensions\Database\Exceptions\DeleteReferencedException;
use Jinya\Router\Extensions\Database\Exceptions\MissingFieldsException;
use Jinya\Router\Extensions\Database\Exceptions\NotFoundException;
use Jinya\Router\Extensions\Database\Exceptions\UpdateColumnIsNullException;
use Jinya\Router\Extensions\Database\Exceptions\UpdateReferenceFailedException;
use Jinya\Router\Extensions\Database\Exceptions\UpdateUniqueFailedException;
use JsonException;
use JsonSerializable;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7Server\ServerRequestCreator;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

class DatabaseRequestHandler
{
    private function encodeBody(
        ServerRequestInterface $request,
        ResponseInterface $response,
        mixed $data
    ): ResponseInterface {
        if ($data instanceof JsonSerializable) {
            $body = $data->jsonSerialize();
        } elseif (method_exists($data, '__serialize')) {
            $body = $data->__serialize();
        } else {
            $body = $data;
        }

        $encodedBody = json_encode($body, JSON_THROW_ON_ERROR);

        return $response
            ->withAddedHeader('Content-Type', 'application/json')
            ->withBody(Stream::create($encodedBody));
    }

    /**
     * @throws NotFoundException
     * @throws JsonException
     */
    private function handleFindByIdResult(
        ServerRequestInterface $request,
        mixed $item
    ): ResponseInterface {
        if ($item === null) {
            throw new NotFoundException($request, 'Entity not found');
        }

        return $this->encodeBody($request, new Response(200), $item);
    }

    /**
     * @param Iterator<Entity> $iterator
     * @throws JsonException
     */
    private function handleFindAllResult(
        ServerRequestInterface $request,
        Iterator $iterator,
        int $offset,
        int|null $total
    ): ResponseInterface {
        $items = iterator_to_array($iterator);
        $payload = [
            'offset' => $offset,
            'itemsCount' => count($items),
            'totalCount' => $total ?? count($items),
            'items' => $items,
        ];

        return $this->encodeBody($request, new Response(200), $payload);
    }

    private function handleDeleteResult(): ResponseInterface
    {
        return new Response(204);
    }

    private function handleUpdateResult($item): ResponseInterface
    {
        return new Response(204);
    }

    private function handleCreateResult($item): ResponseInterface
    {
        return new Response(
            201,
            ['Content-Type' => 'application/json'],
            json_encode($item, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @throws MissingFieldsException
     */
    private function checkRequiredFields(ServerRequestInterface $request, array $fields): void
    {
        $body = $request->getParsedBody();
        if (!$body) {
            throw new MissingFieldsException(
                $request,
                $fields,
                'Required fields are missing'
            );
        }
        $missingFields = [];
        foreach ($fields as $key) {
            if (!array_key_exists($key, $body)) {
                $missingFields[] = $key;
            }
        }

        if (!empty($missingFields)) {
            throw new MissingFieldsException(
                $request,
                $fields,
                'Required fields are missing'
            );
        }
    }

    /**
     * @throws JsonException
     */
    private function getRequest(bool $parseBody = false): ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $creator = new ServerRequestCreator(
            $psr17Factory, // ServerRequestFactory
            $psr17Factory, // UriFactory
            $psr17Factory, // UploadedFileFactory
            $psr17Factory  // StreamFactory
        );

        $request = $creator->fromGlobals();
        $contentTypeHeader = $request->getHeader('Content-Type');
        if (!empty($contentTypeHeader) && $parseBody) {
            $contentType = $contentTypeHeader[0];
            if ($contentType === 'application/json') {
                $request = $request->withParsedBody(
                    json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR)
                );
            }
        }

        return $request;
    }

    /**
     * @throws JsonException
     */
    public function handleGetAllRequest(string $entityClass): ResponseInterface
    {
        $request = $this->getRequest();

        if (!is_subclass_of($entityClass, Findable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }

        try {
            $query = $request->getQueryParams();
            $offset = $query['offset'] ?? 0;
            $count = $query['count'] ?? PHP_INT_MAX;
            $orderBy = $query['orderBy'] ?? 'id';
            $orderByDir = strtoupper($query['orderDirection'] ?? 'ASC');

            if (!property_exists($entityClass, $orderBy)) {
                $orderBy = 'id';
            }
            if ($orderByDir !== 'DESC') {
                $orderByDir = 'ASC';
            }

            $orderClause = "$orderBy $orderByDir";

            $result = $entityClass::findRange($offset, $count, $orderClause);
            $totalCount = $entityClass::countAll();

            return $this->handleFindAllResult($request, $result, $offset, $totalCount);
        } catch (NotFoundException $exception) {
            return Handlers::handleNotFound($request, $exception);
        } catch (JsonException $exception) {
            return Handlers::handleJsonError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
        }
    }

    /**
     * @throws JsonException
     */
    public function handleGetByIdRequest(string $entityClass, int|string $id): ResponseInterface
    {
        $request = $this->getRequest();

        if (!is_subclass_of($entityClass, Findable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }

        try {
            $result = $entityClass::findById($id);

            return $this->handleFindByIdResult($request, $result);
        } catch (NotFoundException $exception) {
            return Handlers::handleNotFound($request, $exception);
        } catch (JsonException $exception) {
            return Handlers::handleJsonError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
        }
    }

    /**
     * @throws JsonException
     */
    public function handleDeleteRequest(string $entityClass, int|string $id): ResponseInterface
    {
        $request = $this->getRequest();

        if (!is_subclass_of($entityClass, Findable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }
        if (!is_subclass_of($entityClass, Deletable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Deletable interface")
            );
        }

        try {
            $entity = $entityClass::findById($id);
            if ($entity === null) {
                throw new NotFoundException($request, "The entity was not found");
            }

            $entity->delete();

            return $this->handleDeleteResult();
        } catch (NotFoundException $exception) {
            return Handlers::handleNotFound($request, $exception);
        } catch (JsonException $exception) {
            return Handlers::handleJsonError($request, $exception);
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo;
            if ($errorInfo[0] === '23000') {
                return Handlers::handleDeleteReferencedError(
                    $request,
                    new DeleteReferencedException($request, $entity ?? null, "The entity is referenced")
                );
            }

            return Handlers::handleInternalServerError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
        }
    }

    /**
     * @param string[] $requiredFields
     * @throws JsonException
     */
    public function handleCreateRequest(string $entityClass, array $requiredFields): ResponseInterface
    {
        $request = $this->getRequest(true);

        if (!is_subclass_of($entityClass, Creatable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }

        $entity = new $entityClass();
        try {
            $this->checkRequiredFields($request, $requiredFields);
            $body = $request->getParsedBody();
            foreach ($body as $key => $value) {
                $entity->$key = $value;
            }

            $entity->create();

            return $this->handleCreateResult($entity);
        } catch (MissingFieldsException $exception) {
            return Handlers::handleMissingFieldsError($request, $exception);
        } catch (JsonException $exception) {
            return Handlers::handleJsonError($request, $exception);
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo;
            if ($errorInfo[0] === '23004'||($errorInfo[0] === '23000' && $errorInfo[1] === '1022')) {
                // MySQL handling
                return Handlers::handleCreateUniqueFailedError(
                    $request,
                    new CreateUniqueFailedException($request, $entity, $exception->getMessage(), $exception)
                );
            }
            
            if ($errorInfo[0] === '23002' || ($errorInfo[0] === '23000' && $errorInfo[1] === '1048')) {
                return Handlers::handleCreateColumnIsNullError(
                    $request,
                    new CreateColumnIsNullException($request, $entity, $exception->getMessage(), $exception)
                );
            } 
            
            if ($errorInfo[0] === '23003'||($errorInfo[0] === '23000' && $errorInfo[1] === '1062')) {
                return Handlers::handleCreateReferenceFailedError(
                    $request,
                    new CreateReferenceFailedException($request, $entity, $exception->getMessage(), $exception)
                );
            }

            return Handlers::handleInternalServerError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
        }
    }

    /**
     * @param class-string<Updatable&Findable> $entityClass
     * @throws JsonException
     */
    public function handleUpdateRequest(string $entityClass, int|string $id): ResponseInterface
    {
        $request = $this->getRequest(true);

        if (!is_subclass_of($entityClass, Creatable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }

        try {
            $entity = $entityClass::findById($id);
            $body = $request->getParsedBody();
            foreach ($body as $key => $value) {
                $entity->$key = $value;
            }

            $entity->update();

            return $this->handleUpdateResult($entity);
        } catch (MissingFieldsException $exception) {
            return Handlers::handleMissingFieldsError($request, $exception);
        } catch (JsonException $exception) {
            return Handlers::handleJsonError($request, $exception);
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo;
            if ($errorInfo[0] === '23004'||($errorInfo[0] === '23000' && $errorInfo[1] === '1022')) {
                return Handlers::handleUpdateUniqueFailedError(
                    $request,
                    new UpdateUniqueFailedException($request, $entity ?? null, $exception->getMessage(), $exception)
                );
            }

            if ($errorInfo[0] === '23002' || ($errorInfo[0] === '23000' && $errorInfo[1] === '1048')) {
                return Handlers::handleUpdateColumnIsNullError(
                    $request,
                    new UpdateColumnIsNullException($request, $entity ?? null, $exception->getMessage(), $exception)
                );
            }

            if ($errorInfo[0] === '23003'||($errorInfo[0] === '23000' && $errorInfo[1] === '1062')) {
                return Handlers::handleUpdateReferenceFailedError(
                    $request,
                    new UpdateReferenceFailedException($request, $entity ?? null, $exception->getMessage(), $exception)
                );
            }

            return Handlers::handleInternalServerError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
        }
    }
}
