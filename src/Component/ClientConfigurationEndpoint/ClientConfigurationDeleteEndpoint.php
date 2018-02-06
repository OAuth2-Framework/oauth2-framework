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

namespace OAuth2Framework\Component\ClientConfigurationEndpoint;

use Http\Message\ResponseFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\Command\DeleteClientCommand;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

class ClientConfigurationDeleteEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * ClientConfigurationDeleteEndpoint constructor.
     *
     * @param MessageBus      $messageBus
     * @param ResponseFactory $responseFactory
     */
    public function __construct(MessageBus $messageBus, ResponseFactory $responseFactory)
    {
        $this->messageBus = $messageBus;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        /**
         * @var Client
         */
        $client = $request->getAttribute('client');
        $id = $client->getPublicId();
        $command = DeleteClientCommand::create($id);
        $this->messageBus->handle($command);

        $response = $this->responseFactory->createResponse(204);
        $headers = ['Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
