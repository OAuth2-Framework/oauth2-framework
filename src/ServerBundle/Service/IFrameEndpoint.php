<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Service;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Twig\Environment;

final class IFrameEndpoint implements MiddlewareInterface
{
    private Environment $templateEngine;

    private ResponseFactoryInterface $responseFactory;

    private string $template;

    private string $storageName;

    public function __construct(Environment $templateEngine, ResponseFactoryInterface $responseFactory, string $template, string $storageName)
    {
        $this->templateEngine = $templateEngine;
        $this->responseFactory = $responseFactory;
        $this->template = $template;
        $this->storageName = $storageName;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $content = $this->templateEngine->render($this->template, ['storage_name' => $this->storageName]);
        $response = $this->responseFactory->createResponse();
        $headers = ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write($content);

        return $response;
    }
}
