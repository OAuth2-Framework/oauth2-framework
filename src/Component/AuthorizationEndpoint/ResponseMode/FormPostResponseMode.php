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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use function League\Uri\parse;
use function League\Uri\build;
use League\Uri;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class FormPostResponseMode implements ResponseMode
{
    private FormPostResponseRenderer $renderer;

    public function __construct(FormPostResponseRenderer $renderer)
    {
        $this->renderer = $renderer;
    }

    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FORM_POST;
    }

    public function buildResponse(ResponseInterface $response, string $redirectUri, array $data): ResponseInterface
    {
        $uri = parse($redirectUri);
        $uri['fragment'] = '_=_'; //A redirect Uri is not supposed to have fragment so we override it.
        $uri = build($uri);

        $template = $this->renderer->render($uri, $data);
        $response = $response->withStatus(200);
        $response = $response->withHeader('Content-Type', 'text/html');
        $response->getBody()->write($template);

        return $response;
    }
}
