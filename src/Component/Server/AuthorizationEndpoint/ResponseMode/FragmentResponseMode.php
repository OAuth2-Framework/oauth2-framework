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
use Interop\Http\Factory\UriFactoryInterface;
use League\Uri;
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseMode
{
    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * FragmentResponseMode constructor.
     *
     * @param UriFactoryInterface $uriFactory
     * @param ResponseFactory     $responseFactory
     */
    public function __construct(UriFactoryInterface $uriFactory, ResponseFactory $responseFactory)
    {
        $this->uriFactory = $uriFactory;
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
        if (!array_key_exists('fragment', $uri)) {
            $fragment = $data;
        } else {
            parse_str($uri['fragment'], $fragment);
            $fragment = array_merge($fragment, $data);
        }
        $uri['fragment'] = http_build_query($fragment);
        $rebuiltRedirectUri = Uri\build($uri);

        $response = $this->responseFactory->createResponse(302);
        $response = $response->withHeader('Location', $rebuiltRedirectUri);

        return $response;
    }
}
