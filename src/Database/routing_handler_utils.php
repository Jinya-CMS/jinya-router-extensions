<?php

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;

function handle_request(string $class): \Psr\Http\Message\ResponseInterface
{
    return new \Nyholm\Psr7\Response();
}

function get_request(bool $parseBody = false): ServerRequestInterface
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
                (array)(json_decode($request->getBody()->getContents(), true, 512) ?: [])
            );
        }
    }

    return $request;
}
