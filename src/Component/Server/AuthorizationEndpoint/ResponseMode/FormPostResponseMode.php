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

namespace OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseMode;

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class FormPostResponseMode implements ResponseMode
{
    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var FormPostResponseRenderer
     */
    private $renderer;

    /**
     * FormPostResponseMode constructor.
     *
     * @param FormPostResponseRenderer $renderer
     * @param ResponseFactory          $responseFactory
     */
    public function __construct(FormPostResponseRenderer $renderer, ResponseFactory $responseFactory)
    {
        $this->responseFactory = $responseFactory;
        $this->renderer = $renderer;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FORM_POST;
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
