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

use Interop\Http\Factory\ResponseFactoryInterface;
use League\Uri;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseMode
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * FragmentResponseMode constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return ResponseType::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = Uri\parse($redirectUri);
        $data['_'] = '_';
        $uri['fragment'] = Uri\build_query($data); //A redirect Uri is not supposed to have fragment so we override it.
        $uri = Uri\build($uri);

        $response = $this->responseFactory->createResponse(302);
        $response = $response->withHeader('Location', $uri);

        return $response;
    }
}
