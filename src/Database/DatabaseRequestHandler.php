<?php

namespace Jinya\Router\Extensions\Database;

use DateTime;
use DateTimeInterface;
use Iterator;
use Jinya\Database\Creatable;
use Jinya\Database\Deletable;
use Jinya\Database\Exception\NotNullViolationException;
use Jinya\Database\Findable;
use Jinya\Database\Updatable;
use Jinya\Router\Extensions\Database\Exceptions\CreateColumnIsNullException;
use Jinya\Router\Extensions\Database\Exceptions\CreateReferenceFailedException;
use Jinya\Router\Extensions\Database\Exceptions\CreateUniqueFailedException;
use Jinya\Router\Extensions\Database\Exceptions\DeleteReferencedException;
use Jinya\Router\Extensions\Database\Exceptions\InvalidDateFormatException;
use Jinya\Router\Extensions\Database\Exceptions\MissingFieldsException;
use Jinya\Router\Extensions\Database\Exceptions\NotFoundException;
use Jinya\Router\Extensions\Database\Exceptions\UpdateColumnIsNullException;
use Jinya\Router\Extensions\Database\Exceptions\UpdateReferenceFailedException;
use Jinya\Router\Extensions\Database\Exceptions\UpdateUniqueFailedException;
use JsonException;
use JsonSerializable;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\Stream;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;
use TypeError;

/**
 * @internal
 */
class DatabaseRequestHandler
{
    /**
     * @param object|array<array-key, mixed> $data
     * @throws JsonException
     */
    private function encodeBody(
        ServerRequestInterface $request,
        ResponseInterface $response,
        object|array $data
    ): ResponseInterface {
        if ($data instanceof JsonSerializable) {
            $body = $data->jsonSerialize();
        } elseif (is_object($data) && method_exists($data, '__serialize')) {
            // @codeCoverageIgnoreStart
            $body = $data->__serialize();
            // @codeCoverageIgnoreEnd
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
        object|null $item
    ): ResponseInterface {
        if ($item === null) {
            throw new NotFoundException($request, 'Entity not found');
        }

        return $this->encodeBody($request, new Response(200), $item);
    }

    /**
     * @param Iterator<Findable> $iterator
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

    private function handleUpdateResult(): ResponseInterface
    {
        return new Response(204);
    }

    /**
     * @param object|array<array-key, mixed> $item
     * @throws JsonException
     */
    private function handleCreateResult(ServerRequestInterface $request, object|array $item): ResponseInterface
    {
        return $this->encodeBody($request, new Response(201), $item);
    }

    /**
     * @param string[] $fields
     * @throws MissingFieldsException
     */
    private function checkRequiredFields(ServerRequestInterface $request, array $fields): void
    {
        $body = $request->getParsedBody();
        if (!$body || !is_array($body)) {
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
     * @param class-string<Findable> $entityClass
     */
    public function handleGetAllRequest(
        ServerRequestInterface $request,
        string $entityClass
    ): ResponseInterface {
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
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param class-string<Findable> $entityClass
     */
    public function handleGetByIdRequest(
        ServerRequestInterface $request,
        string $entityClass,
        int|string $id
    ): ResponseInterface {
        if (!is_subclass_of($entityClass, Findable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }

        try {
            /** @var Findable|null $result */
            $result = $entityClass::findById($id);

            return $this->handleFindByIdResult($request, $result);
        } catch (NotFoundException $exception) {
            return Handlers::handleNotFound($request, $exception);
            // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param class-string<Findable&Deletable> $entityClass
     */
    public function handleDeleteRequest(
        ServerRequestInterface $request,
        string $entityClass,
        int|string $id
    ): ResponseInterface {
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
            /** @var (Findable&Deletable)|null $entity */
            $entity = $entityClass::findById($id);
            if ($entity === null) {
                throw new NotFoundException($request, "The entity was not found");
            }

            $entity->delete();

            return $this->handleDeleteResult();
        } catch (NotFoundException $exception) {
            return Handlers::handleNotFound($request, $exception);
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo ?? [''];
            if ($errorInfo[0] === '23503' || $errorInfo[0] === '23000') {
                return Handlers::handleDeleteReferencedError(
                    $request,
                    new DeleteReferencedException($request, $entity ?? null, "The entity is referenced")
                );
            }

            // @codeCoverageIgnoreStart
            return Handlers::handleInternalServerError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param class-string<Creatable> $entityClass
     * @param array<string, array{default: mixed|null, required: bool|null, type: string}> $fields
     */
    public function handleCreateRequest(
        ServerRequestInterface $request,
        string $entityClass,
        array $fields
    ): ResponseInterface {
        if (!is_subclass_of($entityClass, Creatable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Creatable interface")
            );
        }

        $entity = new $entityClass();
        try {
            $requiredFields = [];
            foreach ($fields as $name => $field) {
                if ($field['required'] ?? false) {
                    $requiredFields[] = $name;
                }
            }

            $this->checkRequiredFields($request, $requiredFields);
            $this->fillEntityFields($fields, $request, $entity);

            $entity->create();

            return $this->handleCreateResult($request, $entity);
        } catch (MissingFieldsException $exception) {
            return Handlers::handleMissingFieldsError($request, $exception);
        } catch (InvalidDateFormatException $exception) {
            return Handlers::handleInvalidDateFormatError($request, $exception);
        } catch (NotNullViolationException $exception) {
            return Handlers::handleCreateColumnIsNullError(
                $request,
                new CreateColumnIsNullException($request, $entity, $exception->getMessage(), $exception)
            );
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo ?? ['', ''];
            if ($errorInfo[0] === '23505' || ($errorInfo[0] === '23000' && $errorInfo[1] === 1062)) {
                return Handlers::handleCreateUniqueFailedError(
                    $request,
                    new CreateUniqueFailedException($request, $entity, $exception->getMessage(), $exception)
                );
            }

            if ($errorInfo[0] === '23503' || ($errorInfo[0] === '23000' && $errorInfo[1] === 1452)) {
                return Handlers::handleCreateReferenceFailedError(
                    $request,
                    new CreateReferenceFailedException($request, $entity, $exception->getMessage(), $exception)
                );
            }

            // @codeCoverageIgnoreStart
            return Handlers::handleInternalServerError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param class-string<Updatable&Findable> $entityClass
     * @param array<string, array{type: string}> $fields
     */
    public function handleUpdateRequest(
        ServerRequestInterface $request,
        string $entityClass,
        array $fields,
        int|string $id
    ): ResponseInterface {
        if (!is_subclass_of($entityClass, Findable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Findable interface")
            );
        }

        if (!is_subclass_of($entityClass, Updatable::class)) {
            return Handlers::handleInternalServerError(
                $request,
                new RuntimeException("Entity does not implement Updatable interface")
            );
        }

        try {
            /** @var Findable&Updatable $entity */
            $entity = $entityClass::findById($id);
            $this->fillEntityFields($fields, $request, $entity);

            $entity->update();

            return $this->handleUpdateResult();
        } catch (InvalidDateFormatException $exception) {
            return Handlers::handleInvalidDateFormatError($request, $exception);
        } catch (NotNullViolationException $exception) {
            return Handlers::handleUpdateColumnIsNullError(
                $request,
                new UpdateColumnIsNullException($request, $entity ?? null, $exception->getMessage(), $exception)
            );
        } catch (PDOException $exception) {
            $errorInfo = $exception->errorInfo ?? ['', ''];
            if ($errorInfo[0] === '23505' || ($errorInfo[0] === '23000' && $errorInfo[1] === 1062)) {
                return Handlers::handleUpdateUniqueFailedError(
                    $request,
                    new UpdateUniqueFailedException($request, $entity ?? null, $exception->getMessage(), $exception)
                );
            }

            if ($errorInfo[0] === '23503' || ($errorInfo[0] === '23000' && $errorInfo[1] === 1452)) {
                return Handlers::handleUpdateReferenceFailedError(
                    $request,
                    new UpdateReferenceFailedException($request, $entity ?? null, $exception->getMessage(), $exception)
                );
            }

            // @codeCoverageIgnoreStart
            return Handlers::handleInternalServerError($request, $exception);
        } catch (Throwable $exception) {
            return Handlers::handleInternalServerError($request, $exception);
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * @param array<string, array{default: mixed|null, required: bool|null, type: string}> $fields
     * @throws InvalidDateFormatException
     */
    public function fillEntityFields(array $fields, ServerRequestInterface $request, Creatable|Updatable $entity): void
    {
        try {
            $fieldNames = array_keys($fields);
            /** @var array<string, mixed> $body */
            $body = $request->getParsedBody();
            $value = null;
            $field = null;
            foreach ($fieldNames as $field) {
                if (property_exists($entity, $field)) {
                    if (array_key_exists($field, $body)) {
                        $value = $body[$field];
                        if ($fields[$field]['type'] === DateTime::class) {
                            $entity->$field = DateTime::createFromFormat(
                                DateTimeInterface::W3C,
                                $body[$field]
                            ) ?: throw new InvalidDateFormatException(
                                $request,
                                $body[$field],
                                'The date has an invalid format'
                            );
                        } else {
                            $entity->$field = $value;
                        }
                    } elseif ($entity instanceof Creatable && ($fields[$field]['default'] ?? false)) {
                        $entity->$field = $fields[$field]['default'];
                    }
                }
            }
        } catch (TypeError $error) {
            if ($value === null) {
                throw new NotNullViolationException([$field]);
            }
        }
    }
}
