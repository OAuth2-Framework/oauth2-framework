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
use OAuth2Framework\Component\ClientRule\RuleManager;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Client\ClientRepository;
use OAuth2Framework\Component\Core\DataBag\DataBag;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ClientConfigurationPutEndpoint implements MiddlewareInterface
{
    /**
     * @var ClientRepository
     */
    private $clientRepository;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var RuleManager
     */
    private $ruleManager;

    public function __construct(ClientRepository $clientRepository, ResponseFactory $responseFactory, RuleManager $ruleManager)
    {
        $this->clientRepository = $clientRepository;
        $this->responseFactory = $responseFactory;
        $this->ruleManager = $ruleManager;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $next): ResponseInterface
    {
        /** @var Client $client */
        $client = $request->getAttribute('client');
        $parameters = RequestBodyParser::parseJson($request);

        $command_parameters = new DataBag($parameters);
        $validated_parameters = $this->ruleManager->handle($client->getClientId(), $command_parameters);
        $client->setParameter($validated_parameters);

        try {
            $this->clientRepository->save($client);
        } catch (\InvalidArgumentException $e) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_REQUEST, $e->getMessage(), [], $e);
        }

        $response = $this->responseFactory->createResponse();
        $response->getBody()->write(\Safe\json_encode($client->all(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }
}
