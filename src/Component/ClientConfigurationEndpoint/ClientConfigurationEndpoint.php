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
use OAuth2Framework\Component\BearerTokenType\BearerToken;
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

class ClientConfigurationEndpoint implements MiddlewareInterface
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var BearerToken
     */
    private $bearerToken;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var RuleManager
     */
    private $ruleManager;

    /**
     * ClientConfigurationEndpoint constructor.
     *
     * @param ClientRepository $clientRepository
     * @param BearerToken      $bearerToken
     * @param MessageBus       $messageBus
     * @param ResponseFactory  $responseFactory
     * @param RuleManager      $ruleManager
     */
    public function __construct(ClientRepository $clientRepository, BearerToken $bearerToken, MessageBus $messageBus, ResponseFactory $responseFactory, RuleManager $ruleManager)
    {
        $this->clientRepository = $clientRepository;
        $this->bearerToken = $bearerToken;
        $this->messageBus = $messageBus;
        $this->responseFactory = $responseFactory;
        $this->ruleManager = $ruleManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        $this->checkClient($request);
        switch ($request->getMethod()) {
            case 'GET':
                $get = new ClientConfigurationGetEndpoint($this->responseFactory);

                return $get->process($request, $next);
            case 'PUT':
                $put = new ClientConfigurationPutEndpoint($this->clientRepository, $this->messageBus, $this->responseFactory, $this->ruleManager);

                return $put->process($request, $next);
            case 'DELETE':
                $delete = new ClientConfigurationDeleteEndpoint($this->messageBus, $this->responseFactory);

                return $delete->process($request, $next);
            default:
                throw new OAuth2Exception(405, OAuth2Exception::ERROR_INVALID_REQUEST, 'Unsupported method.');
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     */
    private function checkClient(ServerRequestInterface $request)
    {
        try {
            $client = $request->getAttribute('client');
            if (!$client instanceof Client) {
                throw new \RuntimeException('Invalid client or invalid registration access token.');
            }
            if (!$client->has('registration_access_token')) {
                throw new \RuntimeException('Invalid client or invalid registration access token.');
            }
            $values = [];
            $token = $this->bearerToken->find($request, $values);
            if (null === $token) {
                throw new \RuntimeException('Invalid client or invalid registration access token.');
            }
            if (!hash_equals($client->get('registration_access_token'), $token)) {
                throw new \InvalidArgumentException('Invalid client or invalid registration access token.');
            }
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, $e->getMessage(), $e);
        }
    }
}
