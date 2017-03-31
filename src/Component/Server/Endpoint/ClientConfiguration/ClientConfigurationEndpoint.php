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

use Assert\Assertion;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenType\BearerToken;
use Psr\Http\Message\ServerRequestInterface;
use SimpleBus\Message\Bus\MessageBus;

final class ClientConfigurationEndpoint implements MiddlewareInterface
{
    /**
     * @var BearerToken
     */
    private $bearerToken;

    /**
     * @var MessageBus
     */
    private $messageBus;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    /**
     * ClientConfigurationEndpoint constructor.
     *
     * @param BearerToken              $bearerToken
     * @param MessageBus               $messageBus
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(BearerToken $bearerToken, MessageBus $messageBus, ResponseFactoryInterface $responseFactory)
    {
        $this->bearerToken = $bearerToken;
        $this->messageBus = $messageBus;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $next)
    {
        $this->checkClient($request);
        switch ($request->getMethod()) {
            case 'GET':
                $get = new ClientConfigurationGetEndpoint($this->responseFactory);

                return $get->process($request, $next);
            case 'PUT':
                $get = new ClientConfigurationPutEndpoint($this->messageBus, $this->responseFactory);

                return $get->process($request, $next);
            case 'DELETE':
                $get = new ClientConfigurationDeleteEndpoint($this->messageBus, $this->responseFactory);

                return $get->process($request, $next);
            default:
                throw new OAuth2Exception(
                    405,
                    [
                        'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                        'error_description' => 'Unsupported method.',
                    ]
                );
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
            Assertion::isInstanceOf($client, Client::class, 'Invalid client or invalid registration access token.');
            Assertion::true($client->has('registration_access_token'), 'Invalid client or invalid registration access token.');
            $values = [];
            $token = $this->bearerToken->findToken($request, $values);
            Assertion::notNull($token, 'Invalid client or invalid registration access token.');
            Assertion::eq($token, $client->get('registration_access_token'), 'Invalid client or invalid registration access token.');
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => $e->getMessage(),
                ]
            );
        }
    }
}
