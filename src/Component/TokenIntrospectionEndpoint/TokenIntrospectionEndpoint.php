<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

use function Safe\json_encode;
use function Safe\sprintf;
use OAuth2Framework\Component\Core\Message\OAuth2Error;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\Util\RequestBodyParser;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use function Safe\array_flip;

final class TokenIntrospectionEndpoint implements MiddlewareInterface
{
    private TokenTypeHintManager $tokenTypeHintManager;

    private ResponseFactoryInterface $responseFactory;

    public function __construct(TokenTypeHintManager $tokenTypeHintManager, ResponseFactoryInterface $responseFactory)
    {
        $this->tokenTypeHintManager = $tokenTypeHintManager;
        $this->responseFactory = $responseFactory;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $resourceServer = $this->getResourceServer($request);
            $token = $this->getToken($request);
            $hints = $this->getTokenTypeHints($request);

            foreach ($hints as $hint) {
                $result = $hint->find($token);
                if (null !== $result) {
                    if (null === $result->getResourceServerId() || $result->getResourceServerId()->getValue() === $resourceServer->getResourceServerId()->getValue()) {
                        $data = $hint->introspect($result);
                        $response = $this->responseFactory->createResponse();
                        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
                        foreach ($headers as $k => $v) {
                            $response = $response->withHeader($k, $v);
                        }

                        return $response;
                    }
                }
            }
            $responseContent = ['active' => false];
            $responseStatucCode = 200;
        } catch (OAuth2Error $e) {
            $responseContent = $e->getData();
            $responseStatucCode = $e->getCode();
        }

        $response = $this->responseFactory->createResponse($responseStatucCode);
        $response->getBody()->write(json_encode($responseContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $headers = ['Content-Type' => 'application/json; charset=UTF-8', 'Cache-Control' => 'no-cache, no-store, max-age=0, must-revalidate, private', 'Pragma' => 'no-cache'];
        foreach ($headers as $k => $v) {
            $response = $response->withHeader($k, $v);
        }

        return $response;
    }

    private function getResourceServer(ServerRequestInterface $request): ResourceServer
    {
        $resourceServer = $request->getAttribute('resource_server');
        if (null === $resourceServer) {
            throw new OAuth2Error(400, OAuth2Error::ERROR_INVALID_RESOURCE_SERVER, 'Resource Server authentication failed.');
        }

        return $resourceServer;
    }

    private function getToken(ServerRequestInterface $request): string
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
    private function getTokenTypeHints(ServerRequestInterface $request): array
    {
        $params = $this->getRequestParameters($request);
        $tokenTypeHints = $this->tokenTypeHintManager->getTokenTypeHints();

        if (\array_key_exists('token_type_hint', $params)) {
            $tokenTypeHint = $params['token_type_hint'];
            if (!\array_key_exists($params['token_type_hint'], $tokenTypeHints)) {
                throw new OAuth2Error(400, 'unsupported_token_type', sprintf('The token type hint "%s" is not supported. Please use one of the following values: %s.', $params['token_type_hint'], implode(', ', array_keys($tokenTypeHints))));
            }

            $hint = $tokenTypeHints[$tokenTypeHint];
            unset($tokenTypeHints[$tokenTypeHint]);
            $tokenTypeHints = [$tokenTypeHint => $hint] + $tokenTypeHints;
        }

        return $tokenTypeHints;
    }

    private function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = RequestBodyParser::parseFormUrlEncoded($request);

        return array_intersect_key($parameters, array_flip(['token', 'token_type_hint']));
    }
}
