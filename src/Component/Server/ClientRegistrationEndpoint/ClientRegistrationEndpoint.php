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

namespace OAuth2Framework\Component\Server\ClientRegistrationEndpoint;

use Http\Message\ResponseFactory;
use Interop\Http\Server\RequestHandlerInterface;
use Interop\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Server\ClientRegistrationEndpoint\Rule\RuleManager;
use OAuth2Framework\Component\Server\Core\Client\Client;
use OAuth2Framework\Component\Server\Core\Client\ClientId;
use OAuth2Framework\Component\Server\Core\Client\ClientRepository;
use OAuth2Framework\Component\Server\Core\Client\Command\CreateClientCommand;
use OAuth2Framework\Component\Server\Core\DataBag\DataBag;
use OAuth2Framework\Component\Server\Core\Response\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;
use SimpleBus\Message\Bus\MessageBus;

final class ClientRegistrationEndpoint implements MiddlewareInterface
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
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var RuleManager
     */
    private $ruleManager;

    /**
     * ClientRegistrationEndpoint constructor.
     *
     * @param ClientRepository $clientRepository
     * @param ResponseFactory  $responseFactory
     * @param MessageBus       $messageBus
     * @param RuleManager      $ruleManager
     */
    public function __construct(ClientRepository $clientRepository, ResponseFactory $responseFactory, MessageBus $messageBus, RuleManager $ruleManager)
    {
        $this->clientRepository = $clientRepository;
        $this->responseFactory = $responseFactory;
        $this->ruleManager = $ruleManager;
        $this->messageBus = $messageBus;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler = null): ResponseInterface
    {
        $this->checkRequest($request);
        $initialAccessToken = $request->getAttribute('initial_access_token');

        try {
            if ($initialAccessToken instanceof InitialAccessToken) {
                $userAccountId = $initialAccessToken->getUserAccountId();
            } else {
                $userAccountId = null;
            }
            $commandParameters = DataBag::create($request->getParsedBody() ?? []);
            $clientId = ClientId::create(Uuid::uuid4()->toString());
            $validatedParameters = $this->ruleManager->handle($clientId, $commandParameters);
            $command = CreateClientCommand::create($clientId, $userAccountId, $validatedParameters);
            $this->messageBus->handle($command);
            $client = $this->clientRepository->find($clientId);
            if (null === $client) {
                throw new \Exception('Unable to create or retrieve the client.');
            }

            return $this->createResponse($client);
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     */
    private function checkRequest(ServerRequestInterface $request)
    {
        if ('POST' !== $request->getMethod()) {
            throw new OAuth2Exception(405, OAuth2Exception::ERROR_INVALID_REQUEST, 'Unsupported method.');
        }
    }

    /**
     * @param Client $client
     *
     * @return ResponseInterface
     */
    private function createResponse(Client $client): ResponseInterface
    {
        $response = $this->responseFactory->createResponse(201);
        foreach (['Content-Type' => 'application/json', 'Cache-Control' => 'no-store', 'Pragma' => 'no-cache'] as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write(json_encode($client->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response;
    }
}
