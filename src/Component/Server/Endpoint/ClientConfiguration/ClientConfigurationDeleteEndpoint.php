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

namespace OAuth2Framework\Component\Server\Endpoint\ClientConfiguration;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Command\Client\DeleteClientCommand;
use OAuth2Framework\Component\Server\Model\Client\Client;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

final class ClientConfigurationDeleteEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * ClientConfigurationDeleteEndpoint constructor.
     *
     * @param MessageBus               $messageBus
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(MessageBus $messageBus, ResponseFactoryInterface $responseFactory)
    {
        $this->messageBus = $messageBus;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $next)
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
