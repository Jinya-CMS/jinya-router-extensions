<?php

namespace Jinya\Router\Extensions\Database;

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
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ErrorHandler
{
    public function handleNotFound(
        ServerRequestInterface $request,
        NotFoundException $notFoundException
    ): ResponseInterface|false;

    public function handleInternalServerError(
        ServerRequestInterface $request,
        Throwable $throwable
    ): ResponseInterface|false;

    public function handleDeleteReferencedError(
        ServerRequestInterface $request,
        DeleteReferencedException $deleteReferencedException
    ): ResponseInterface|false;

    public function handleMissingFieldsError(
        ServerRequestInterface $request,
        MissingFieldsException $missingFieldsException
    ): ResponseInterface|false;

    public function handleCreateReferenceFailedError(
        ServerRequestInterface $request,
        CreateReferenceFailedException $createReferenceFailedException
    ): ResponseInterface|false;

    public function handleCreateColumnIsNullError(
        ServerRequestInterface $request,
        CreateColumnIsNullException $createColumnIsNullException
    ): ResponseInterface|false;

    public function handleCreateUniqueFailedError(
        ServerRequestInterface $request,
        CreateUniqueFailedException $createUniqueFailedException
    ): ResponseInterface|false;

    public function handleUpdateReferenceFailedError(
        ServerRequestInterface $request,
        UpdateReferenceFailedException $updateReferenceFailedException
    ): ResponseInterface|false;

    public function handleUpdateColumnIsNullError(
        ServerRequestInterface $request,
        UpdateColumnIsNullException $updateColumnIsNullException
    ): ResponseInterface|false;

    public function handleUpdateUniqueFailedError(
        ServerRequestInterface $request,
        UpdateUniqueFailedException $updateUniqueFailedException
    ): ResponseInterface|false;

    public function handleInvalidDateFormatError(
        ServerRequestInterface $request,
        InvalidDateFormatException $invalidDateFormatException
    ): ResponseInterface|false;
}
