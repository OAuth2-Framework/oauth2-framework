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
use OAuth2Framework\Component\Server\AuthorizationEndpoint\ResponseType;
use Psr\Http\Message\ResponseInterface;

final class QueryResponseMode implements ResponseMode
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
     * QueryResponseMode constructor.
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
        return ResponseType::RESPONSE_TYPE_MODE_QUERY;
    }

    /**
     * {@inheritdoc}
     */
    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = $this->uriFactory->createUri($redirectUri);
        $uri = $uri->withFragment('_=_');
        parse_str($uri->getQuery(), $queryParams);
        $queryParams += $data;
        $uri = $uri->withQuery(http_build_query($queryParams));

        $response = $this->responseFactory->createResponse(302);
        $response = $response->withHeader('Location', $uri->__toString());

        return $response;
    }
}
