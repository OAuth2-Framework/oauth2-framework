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

namespace OAuth2Framework\Component\ClientRegistrationEndpoint;

use Base64Url\Base64Url;
use Http\Message\ResponseFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientId;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Client\Command\CreateClientCommand;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

class ClientRegistrationEndpoint implements MiddlewareInterface
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
            $clientIdLength = random_int(50, 100);
            $clientId = ClientId::create(Base64Url::encode(random_bytes($clientIdLength)));
            $validatedParameters = $this->ruleManager->handle($clientId, $commandParameters);
            $command = CreateClientCommand::create($clientId, $userAccountId, $validatedParameters);
            $this->messageBus->handle($command);
            $client = $this->clientRepository->find($clientId);
            if (null === $client) {
                throw new \Exception('Unable to create or retrieve the client.');
            }

            return $this->createResponse($client);
        } catch (\Exception $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
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
        foreach (['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-store', 'Pragma' => 'no-cache'] as $k => $v) {
            $response = $response->withHeader($k, $v);
        }
        $response->getBody()->write(json_encode($client->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response;
    }
}
