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

namespace OAuth2Framework\Component\Server\ClientConfigurationEndpoint;

use Http\Message\ResponseFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\Client\Command\UpdateClientCommand;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

final class ClientConfigurationPutEndpoint implements MiddlewareInterface
{
    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * ClientConfigurationPutEndpoint constructor.
     *
     * @param ClientRepository $clientRepository
     * @param MessageBus       $messageBus
     * @param ResponseFactory  $responseFactory
     */
    public function __construct(ClientRepository $clientRepository, MessageBus $messageBus, ResponseFactory $responseFactory)
    {
        $this->messageBus = $messageBus;
        $this->clientRepository = $clientRepository;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        /** @var Client $client */
        $client = $request->getAttribute('client');

        $command_parameters = DataBag::create($request->getParsedBody() ?? []);
        $command = UpdateClientCommand::create($client->getPublicId(), $command_parameters);

        try {
            $this->messageBus->handle($command);
            $client = $this->clientRepository->find($client->getPublicId());
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(json_encode($client->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
