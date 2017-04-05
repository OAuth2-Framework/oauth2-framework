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
use Interop\Http\Factory\UriFactoryInterface;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;

final class FragmentResponseMode implements ResponseModeInterface
{
    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * FragmentResponseMode constructor.
     *
     * @param UriFactoryInterface      $uriFactory
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(UriFactoryInterface $uriFactory, ResponseFactoryInterface $responseFactory)
    {
        $this->uriFactory = $uriFactory;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return ResponseTypeInterface::RESPONSE_TYPE_MODE_FRAGMENT;
    }

    /**
     * {@inheritdoc}
     */
    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = $this->uriFactory->createUri($redirectUri);
        parse_str($uri->getFragment(), $fragmentParams);
        $fragmentParams += $data;
        $uri = $uri->withFragment(http_build_query($fragmentParams));

        $response = $this->responseFactory->createResponse(302);
        $response = $response->withHeader('Location', $uri->__toString());

        return $response;
    }
}
