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

use Http\Message\MessageFactory;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;

final class FormPostResponseMode implements ResponseModeInterface
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * @var FormPostResponseRendererInterface
     */
    private $renderer;

    /**
     * FormPostResponseMode constructor.
     *
     * @param FormPostResponseRendererInterface $renderer
     * @param MessageFactory          $messageFactory
     */
    public function __construct(FormPostResponseRendererInterface $renderer, MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
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
        $response = $this->messageFactory->createResponse();
        $response = $response->withHeader('Content-Type', 'text/html');
        $response->getBody()->write($template);

        return $response;
    }
}
