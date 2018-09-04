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

use Http\Message\ResponseFactory;
use OAuth2Framework\Component\Core\Client\Client;
use OAuth2Framework\Component\Core\Message\OAuth2Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class TokenRevocationEndpoint implements MiddlewareInterface
{
    private $tokenTypeHintManager;
    private $responseFactory;

    public function __construct(TokenTypeHintManager $tokenTypeHintManager, ResponseFactory $responseFactory)
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
                        throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'The parameter "token" is invalid.');
                    }
                }
            }

            return $this->getResponse(200, '', $callback);
        } catch (OAuth2Message $e) {
            return $this->getResponse($e->getCode(), \json_encode($e->getData(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $callback);
        }
    }

    private function getResponse(int $code, string $data, ?string $callback): ResponseInterface
    {
        if (null !== $callback) {
            $data = \sprintf('%s(%s)', $callback, $data);
        }

        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write($data);
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    /**
     * @throws OAuth2Message
     */
    private function getClient(ServerRequestInterface $request): Client
    {
        $client = $request->getAttribute('client');
        if (null === $client) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_CLIENT, 'Client authentication failed.');
        }

        return $client;
    }

    /**
     * @throws OAuth2Message
     */
    protected function getToken(ServerRequestInterface $request): string
    {
        $params = $this->getRequestParameters($request);
        if (!\array_key_exists('token', $params)) {
            throw new OAuth2Message(400, OAuth2Message::ERROR_INVALID_REQUEST, 'The parameter "token" is missing.');
        }

        return $params['token'];
    }

    /**
     * @throws OAuth2Message
     *
     * @return TokenTypeHint[]
     */
    protected function getTokenTypeHints(ServerRequestInterface $request): array
    {
        $params = $this->getRequestParameters($request);
        $tokenTypeHints = $this->tokenTypeHintManager->getTokenTypeHints();

        if (\array_key_exists('token_type_hint', $params)) {
            $tokenTypeHint = $params['token_type_hint'];
            if (!\array_key_exists($params['token_type_hint'], $tokenTypeHints)) {
                throw new OAuth2Message(400, 'unsupported_token_type', \sprintf('The token type hint "%s" is not supported. Please use one of the following values: %s.', $params['token_type_hint'], \implode(', ', \array_keys($tokenTypeHints))));
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
