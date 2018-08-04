<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\ServerBundle\Service;

use Http\Message\MessageFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;

final class IFrameEndpoint implements MiddlewareInterface
{
    /**
     * @var EngineInterface
     */
    private $templateEngine;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

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
     */
    public function __construct(EngineInterface $templateEngine, MessageFactory $messageFactory, string $template, string $storageName)
    {
        $this->templateEngine = $templateEngine;
        $this->messageFactory = $messageFactory;
        $this->template = $template;
        $this->storageName = $storageName;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $content = $this->templateEngine->render($this->template, ['storage_name' => $this->storageName]);
        $response = $this->messageFactory->createResponse();
        $headers = ['Content-Type' => 'text/html; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write($content);

        return $response;
    }
}
