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

namespace OAuth2Framework\Component\Server\ResponseMode;

use Interop\Http\Factory\ResponseFactoryInterface;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;

final class FormPostResponseMode implements ResponseModeInterface
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * @var FormPostResponseRendererInterface
     */
    private $renderer;

    /**
     * FormPostResponseMode constructor.
     *
     * @param FormPostResponseRendererInterface $renderer
     * @param ResponseFactoryInterface          $responseFactory
     */
    public function __construct(FormPostResponseRendererInterface $renderer, ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return ResponseTypeInterface::RESPONSE_TYPE_MODE_FORM_POST;
    }

    /**
     * {@inheritdoc}
     */
    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $template = $this->renderer->render($redirectUri, $data);
        $response = $this->responseFactory->createResponse();
        $response = $response->withHeader('Content-Type', 'text/html');
        $response->getBody()->write($template);

        return $response;
    }
}
