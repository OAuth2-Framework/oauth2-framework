<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2017 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Bundle\Server\Service;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

final class IFrameEndpoint implements MiddlewareInterface
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var string
     */
    private $template;

    /**
     * @var string
     */
    private $storageName;

    /**
     * IFrameEndpoint constructor.
     *
     * @param EngineInterface          $templateEngine
     * @param ResponseFactoryInterface $responseFactory
     * @param string                   $template
     * @param string                   $storageName
     */
    public function __construct(EngineInterface $templateEngine, ResponseFactoryInterface $responseFactory, string $template, string $storageName)
    {
        $this->templateEngine = $templateEngine;
        $this->responseFactory = $responseFactory;
        $this->template = $template;
        $this->storageName = $storageName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
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
