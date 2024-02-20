<?php

namespace Jinya\Router\Extensions\Database;

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
use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

abstract class Handlers
{
    public static ErrorHandler $errorHandler;

    public static function handleNotFound(
        ServerRequestInterface $request,
        NotFoundException $notFoundException
    ): ResponseInterface {
        return self::$errorHandler->handleNotFound($request, $notFoundException) ?: new Response(404);
    }

    public static function handleInternalServerError(
        ServerRequestInterface $request,
        Throwable $throwable
    ): ResponseInterface {
        return self::$errorHandler->handleInternalServerError($request, $throwable) ?: new Response(500);
    }

    public static function handleJsonError(
        ServerRequestInterface $request,
        JsonException $jsonException
    ): ResponseInterface {
        return self::$errorHandler->handleJsonError($request, $jsonException) ?: new Response(500);
    }

    public static function handleDeleteReferencedError(
        ServerRequestInterface $request,
        DeleteReferencedException $deleteReferencedException
    ): ResponseInterface {
        return self::$errorHandler->handleDeleteReferencedError($request, $deleteReferencedException) ?: new Response(409);
    }

    public static function handleMissingFieldsError(
        ServerRequestInterface $request,
        MissingFieldsException $missingFieldsException
    ): ResponseInterface {
        return self::$errorHandler->handleMissingFieldsError($request, $missingFieldsException) ?: new Response(400);
    }

    public static function handleCreateReferenceFailedError(
        ServerRequestInterface $request,
        CreateReferenceFailedException $createReferenceFailedException
    ): ResponseInterface {
        return self::$errorHandler->handleCreateReferenceFailedError($request, $createReferenceFailedException) ?: new Response(409);
    }

    public static function handleCreateColumnIsNullError(
        ServerRequestInterface $request,
        CreateColumnIsNullException $createColumnIsNullException
    ): ResponseInterface {
        return self::$errorHandler->handleCreateColumnIsNullError($request, $createColumnIsNullException) ?: new Response(400);
    }

    public static function handleCreateUniqueFailedError(
        ServerRequestInterface $request,
        CreateUniqueFailedException $createUniqueFailedException
    ): ResponseInterface {
        return self::$errorHandler->handleCreateUniqueFailedError($request, $createUniqueFailedException) ?: new Response(400);
    }

    public static function handleUpdateReferenceFailedError(
        ServerRequestInterface $request,
        UpdateReferenceFailedException $updateReferenceFailedException
    ): ResponseInterface {
        return self::$errorHandler->handleUpdateReferenceFailedError($request, $updateReferenceFailedException) ?: new Response(409);
    }

    public static function handleUpdateColumnIsNullError(
        ServerRequestInterface $request,
        UpdateColumnIsNullException $updateColumnIsNullException
    ): ResponseInterface {
        return self::$errorHandler->handleUpdateColumnIsNullError($request, $updateColumnIsNullException) ?: new Response(400);
    }

    public static function handleUpdateUniqueFailedError(
        ServerRequestInterface $request,
        UpdateUniqueFailedException $updateUniqueFailedException
    ): ResponseInterface {
        return self::$errorHandler->handleUpdateUniqueFailedError($request, $updateUniqueFailedException) ?: new Response(400);
    }
}
