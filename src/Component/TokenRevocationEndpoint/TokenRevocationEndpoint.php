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

namespace OAuth2Framework\Component\TokenRevocationEndpoint;

use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class TokenRevocationEndpoint implements MiddlewareInterface
{
    /**
     * @var TokenTypeHintManager
     */
    private $tokenTypeHintManager;

    /**
     * @var ResponseFactoryInterface
     */
    private $responseFactory;

    public function __construct(TokenTypeHintManager $tokenTypeHintManager, ResponseFactoryInterface $responseFactory)
    {
        $this->tokenTypeHintManager = $tokenTypeHintManager;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $callback = $this->getCallback($request);

        try {
            $client = $this->getClient($request);
            $token = $this->getToken($request);
            $hints = $this->getTokenTypeHints($request);

            foreach ($hints as $hint) {
                $result = $hint->find($token);
                if (null !== $result) {
                    if ($client->getPublicId()->getValue() === $result->getClientId()->getValue()) {
                        $hint->revoke($result);

                        return $this->getResponse(200, '', $callback);
                    } else {
                        throw OAuth2Error::invalidRequest('The parameter "token" is invalid.');
                    }
                }
            }

            return $this->getResponse(200, '', $callback);
        } catch (OAuth2Error $e) {
            return $this->getResponse($e->getCode(), \Safe\json_encode($e->getData(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $callback);
        }
    }

    private function getResponse(int $code, string $data, ?string $callback): ResponseInterface
    {
        if (null !== $callback) {
            $data = \Safe\sprintf('%s(%s)', $callback, $data);
        }

        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write($data);
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    private function getClient(ServerRequestInterface $request): Client
    {
        $client = $request->getAttribute('client');
        if (null === $client) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        return $client;
    }

    protected function getToken(ServerRequestInterface $request): string
    {
        $params = $this->getRequestParameters($request);
        if (!\array_key_exists('token', $params)) {
            throw OAuth2Error::invalidRequest('The parameter "token" is missing.');
        }

        return $params['token'];
    }

    /**
     * @return TokenTypeHint[]
     */
    protected function getTokenTypeHints(ServerRequestInterface $request): array
    {
        $params = $this->getRequestParameters($request);
        $tokenTypeHints = $this->tokenTypeHintManager->getTokenTypeHints();

        if (\array_key_exists('token_type_hint', $params)) {
            $tokenTypeHint = $params['token_type_hint'];
            if (!\array_key_exists($params['token_type_hint'], $tokenTypeHints)) {
                throw new OAuth2Error(400, 'unsupported_token_type', \Safe\sprintf('The token type hint "%s" is not supported. Please use one of the following values: %s.', $params['token_type_hint'], \implode(', ', \array_keys($tokenTypeHints))));
            }

            $hint = $tokenTypeHints[$tokenTypeHint];
            unset($tokenTypeHints[$tokenTypeHint]);
            $tokenTypeHints = [$tokenTypeHint => $hint] + $tokenTypeHints;
        }

        return $tokenTypeHints;
    }

    protected function getCallback(ServerRequestInterface $request): ?string
    {
        $params = $this->getRequestParameters($request);
        if (\array_key_exists('callback', $params)) {
            return $params['callback'];
        }

        return null;
    }

    abstract protected function getRequestParameters(ServerRequestInterface $request): array;
}
