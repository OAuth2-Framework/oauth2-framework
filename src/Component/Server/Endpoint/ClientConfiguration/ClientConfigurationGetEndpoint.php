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

namespace OAuth2Framework\Component\Server\Endpoint\ClientConfiguration;

use Http\Message\MessageFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ClientConfigurationGetEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageFactory
     */
    private $messageFactory;

    /**
     * ClientConfigurationGetEndpoint constructor.
     *
     * @param MessageFactory $messageFactory
     */
    public function __construct(MessageFactory $messageFactory)
    {
        $this->messageFactory = $messageFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next)
    {
        $client = $request->getAttribute('client');
        $response = $this->messageFactory->createResponse();
        $response->getBody()->write(json_encode($client->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
