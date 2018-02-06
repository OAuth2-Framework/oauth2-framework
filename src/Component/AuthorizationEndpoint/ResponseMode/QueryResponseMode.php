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

use Interop\Http\Factory\ResponseFactoryInterface;
use League\Uri;
use OAuth2Framework\Component\AuthorizationEndpoint\ResponseType;
use Psr\Http\Message\ResponseInterface;

class QueryResponseMode implements ResponseMode
{
    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * QueryResponseMode constructor.
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
        return ResponseType::RESPONSE_TYPE_MODE_QUERY;
    }

    /**
     * {@inheritdoc}
     */
    public function buildResponse(string $redirectUri, array $data): ResponseInterface
    {
        $uri = Uri\parse($redirectUri);
        if (array_key_exists('query', $uri) && null !== $uri['query']) {
            $query = Uri\parse_query($uri['query']);
            $data = array_merge($query, $data);
        }
        $uri['query'] = Uri\build_query($data);
        $uri['fragment'] = '_=_'; //A redirect Uri is not supposed to have fragment so we override it.
        $uri = Uri\build($uri);

        $response = $this->responseFactory->createResponse(302);
        $response = $response->withHeader('Location', $uri);

        return $response;
    }
}
