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

namespace OAuth2Framework\Component\AuthorizationEndpoint\ResponseMode;

use League\Uri;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseMode
{
    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    public function buildResponse(ResponseInterface $response, string $redirectUri, array $data): ResponseInterface
    {
        $uri = Uri\parse($redirectUri);
        $data['_'] = '_';
        $uri['fragment'] = Uri\build_query($data); //A redirect Uri is not supposed to have fragment so we override it.
        $uri = Uri\build($uri);

        $response = $response->withStatus(303);
        $response = $response->withHeader('Location', $uri);

        return $response;
    }
}
