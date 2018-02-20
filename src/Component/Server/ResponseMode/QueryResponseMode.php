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

namespace OAuth2Framework\Component\Server\ResponseMode;

use Http\Message\MessageFactory;
use Interop\Http\Factory\UriFactoryInterface;
use OAuth2Framework\Component\Server\ResponseType\ResponseTypeInterface;
use Psr\Http\Message\ResponseInterface;

final class QueryResponseMode implements ResponseModeInterface
{
    /**
     * @var UriFactoryInterface
     */
    private $uriFactory;

    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * QueryResponseMode constructor.
     *
     * @param UriFactoryInterface $uriFactory
     * @param MessageFactory      $messageFactory
     */
    public function __construct(UriFactoryInterface $uriFactory, MessageFactory $messageFactory)
    {
        $this->uriFactory = $uriFactory;
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return ResponseTypeInterface::RESPONSE_TYPE_MODE_QUERY;
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

        $response = $this->messageFactory->createResponse(302);
        $response = $response->withHeader('Location', $uri->__toString());

        return $response;
    }
}
