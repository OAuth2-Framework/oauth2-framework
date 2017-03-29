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

namespace OAuth2Framework\Component\Server\Endpoint\TokenRevocation;

use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use OAuth2Framework\Component\Server\Model\Client\Client;
use OAuth2Framework\Component\Server\Response\OAuth2Exception;
use OAuth2Framework\Component\Server\Response\OAuth2ResponseFactoryManager;
use OAuth2Framework\Component\Server\TokenTypeHint\TokenTypeHintInterface;
use OAuth2Framework\Component\Server\TokenTypeHint\TokenTypeHintManager;
use Psr\Http\Message\ServerRequestInterface;

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

    /**
     * TokenRevocationEndpoint constructor.
     *
     * @param TokenTypeHintManager     $tokenTypeHintManager
     * @param ResponseFactoryInterface $responseFactory
     */
    public function __construct(TokenTypeHintManager $tokenTypeHintManager, ResponseFactoryInterface $responseFactory)
    {
        $this->tokenTypeHintManager = $tokenTypeHintManager;
        $this->responseFactory = $responseFactory;
    }

    /**
     * @return TokenTypeHintManager
     */
    protected function getTokenTypeHintManager(): TokenTypeHintManager
    {
        return $this->tokenTypeHintManager;
    }

    /**
     * @param string $tokenTypeHint
     *
     * @throws OAuth2Exception
     *
     * @return TokenTypeHintInterface[]
     */
    protected function getTokenTypeHint(string $tokenTypeHint): array
    {
        $tokenTypeHints = $this->getTokenTypeHintManager()->getTokenTypeHints();
        if (!array_key_exists($tokenTypeHint, $tokenTypeHints)) {
            throw new OAuth2Exception(
                400,
                [
                    'error' => 'unsupported_token_type',
                    'error_description' => sprintf('The token type hint \'%s\' is not supported. Please use one of the following values: %s.', $tokenTypeHint, implode(', ', array_keys($tokenTypeHints))),
                ]
            );
        }

        $key = array_search($tokenTypeHint, $tokenTypeHints);
        unset($tokenTypeHints[$key]);
        array_unshift($tokenTypeHints, $tokenTypeHint);

        return $tokenTypeHints;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
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
                        throw new OAuth2Exception(
                            400,
                            [
                                'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                                'error_description' => 'The parameter \'token\' is invalid.',
                            ]
                        );
                    }
                }
            }

            return $this->getResponse(200, '', $callback);
        } catch (OAuth2Exception $e) {
            return $this->getResponse($e->getCode(), json_encode($e->getData()), $callback);
        }
    }

    /**
     * @param int         $code
     * @param string      $data
     * @param null|string $callback
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function getResponse($code, $data, $callback)
    {
        if (null !== $callback) {
            $data = sprintf('%s(%s)', $callback, $data);
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
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     *
     * @return Client
     */
    private function getClient(ServerRequestInterface $request): Client
    {
        $client = $request->getAttribute('client');
        if (null === $client) {
            throw new OAuth2Exception(
                401,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_CLIENT,
                    'error_description' => 'Client authentication failed.',
                ]
            );
        }

        return $client;
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
            throw new OAuth2Exception(
                400,
                [
                    'error' => OAuth2ResponseFactoryManager::ERROR_INVALID_REQUEST,
                    'error_description' => 'The parameter \'token\' is missing.',
                ]
            );
        }

        return $params['token'];
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws OAuth2Exception
     *
     * @return TokenTypeHintInterface[]
     */
    protected function getTokenTypeHints(ServerRequestInterface $request): array
    {
        $params = $this->getRequestParameters($request);
        $tokenTypeHints = $this->getTokenTypeHintManager()->getTokenTypeHints();

        if (array_key_exists('token_type_hint', $params)) {
            $tokenTypeHint = $params['token_type_hint'];
            if (!array_key_exists($params['token_type_hint'], $tokenTypeHints)) {
                throw new OAuth2Exception(
                    400,
                    [
                        'error' => 'unsupported_token_type',
                        'error_description' => sprintf('The token type hint \'%s\' is not supported. Please use one of the following values: %s.', $params['token_type_hint'], implode(', ', array_keys($tokenTypeHints))),
                    ]
                );
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
     * @return null|string
     */
    protected function getCallback(ServerRequestInterface $request)
    {
        $params = $this->getRequestParameters($request);
        if (array_key_exists('callback', $params)) {
            return $params['callback'];
        }
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return array
     */
    abstract protected function getRequestParameters(ServerRequestInterface $request): array;
}
