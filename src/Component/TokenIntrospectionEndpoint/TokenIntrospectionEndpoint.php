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

namespace OAuth2Framework\Component\TokenIntrospectionEndpoint;

use Http\Message\ResponseFactory;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use OAuth2Framework\Component\Core\ResourceServer\ResourceServer;
use OAuth2Framework\Component\Core\Exception\OAuth2Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class TokenIntrospectionEndpoint implements MiddlewareInterface
{
    /**
     * @var TokenTypeHintManager
     */
    private $tokenTypeHintManager;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * TokenIntrospectionEndpoint constructor.
     *
     * @param TokenTypeHintManager $tokenTypeHintManager
     * @param ResponseFactory      $responseFactory
     */
    public function __construct(TokenTypeHintManager $tokenTypeHintManager, ResponseFactory $responseFactory)
    {
        $this->tokenTypeHintManager = $tokenTypeHintManager;
        $this->responseFactory = $responseFactory;
    }

    /**
     * {@inheritdoc}
     */
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
        } catch (OAuth2Exception $e) {
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

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     *
     * @return ResourceServer
     */
    private function getResourceServer(ServerRequestInterface $request): ResourceServer
    {
        $resourceServer = $request->getAttribute('resource_server');
        if (null === $resourceServer) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_RESOURCE_SERVER, 'Resource Server authentication failed.');
        }

        return $resourceServer;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     *
     * @return string
     */
    protected function getToken(ServerRequestInterface $request): string
    {
        $params = $this->getRequestParameters($request);
        if (!array_key_exists('token', $params)) {
            throw new OAuth2Exception(400, OAuth2Exception::ERROR_INVALID_REQUEST, 'The parameter "token" is missing.');
        }

        return $params['token'];
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     *
     * @return TokenTypeHint[]
     */
    protected function getTokenTypeHints(ServerRequestInterface $request): array
    {
        $params = $this->getRequestParameters($request);
        $tokenTypeHints = $this->tokenTypeHintManager->getTokenTypeHints();

        if (array_key_exists('token_type_hint', $params)) {
            $tokenTypeHint = $params['token_type_hint'];
            if (!array_key_exists($params['token_type_hint'], $tokenTypeHints)) {
                throw new OAuth2Exception(400, 'unsupported_token_type', sprintf('The token type hint "%s" is not supported. Please use one of the following values: %s.', $params['token_type_hint'], implode(', ', array_keys($tokenTypeHints))));
            }

            $hint = $tokenTypeHints[$tokenTypeHint];
            unset($tokenTypeHints[$tokenTypeHint]);
            $tokenTypeHints = [$tokenTypeHint => $hint] + $tokenTypeHints;
        }

        return $tokenTypeHints;
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    protected function getRequestParameters(ServerRequestInterface $request): array
    {
        $parameters = $request->getParsedBody() ?? [];

        return array_intersect_key($parameters, array_flip(['token', 'token_type_hint']));
    }
}
